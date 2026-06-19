<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\OcrLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;

class OCRService
{
    /**
     * Extract structured fields from a document file.
     */
    public function extract(string $filePath, string $mimeType, int $userId): array
    {
        $apiKey = Setting::get('openrouter_api_key');
        $model = Setting::get('openrouter_model', 'google/gemini-2.5-flash');

        $text = '';
        $isPdf = str_contains($mimeType, 'pdf');

        if ($isPdf) {
            try {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($filePath);
                $text = $pdf->getText();
            } catch (\Exception $e) {
                Log::warning('PDF text extraction failed, falling back: ' . $e->getMessage());
            }
        }

        if (empty($apiKey)) {
            // Mock Mode for local testing when API credentials are not set
            $mockData = $this->getMockData($isPdf ? 'pdf' : 'image');
            
            OcrLog::create([
                'file_path' => basename($filePath),
                'response_data' => ['mock' => true, 'info' => 'OpenRouter API Key is missing. Operating in mock mode.'],
                'extracted_fields' => $mockData,
                'user_id' => $userId,
                'status' => 'Success'
            ]);

            return [
                'success' => true,
                'mock' => true,
                'data' => $mockData
            ];
        }

        // Prompt asking for raw JSON
        $prompt = "Analyze this school application document and extract the applicant's details. Return ONLY a valid raw JSON object with the following keys. No markdown code blocks (do not wrap in ```json), no other text:
{
  \"surname\": \"Last name / Family name\",
  \"first_name\": \"Given first name\",
  \"other_name\": \"Middle name or other name, null if none\",
  \"date_of_birth\": \"Date of birth in YYYY-MM-DD format, null if not found\",
  \"gender\": \"Male or Female, null if not found\",
  \"previous_school\": \"Name of the school / primary school attended, null if not found\",
  \"exam_scores\": \"Any score, overall grade, or results found, null if none\"
}";

        $messages = [];
        if ($isPdf && !empty($text)) {
            $messages[] = [
                'role' => 'user',
                'content' => $prompt . "\n\nDocument Text:\n" . $text
            ];
        } else {
            // Multimodal input: read image and encode as base64
            $fileData = base64_encode(file_get_contents($filePath));
            $dataUrl = 'data:' . $mimeType . ';base64,' . $fileData;

            $messages[] = [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $prompt
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $dataUrl
                        ]
                    ]
                ]
            ];
        }

        try {
            $response = Http::timeout(45)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'HTTP-Referer' => config('app.url', 'http://localhost'),
                    'X-Title' => "St. Augustine's College Admission Portal"
                ])
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.1
                ]);

            $responseData = $response->json();
            
            if ($response->successful() && isset($responseData['choices'][0]['message']['content'])) {
                $content = $responseData['choices'][0]['message']['content'];
                
                // Clean JSON formatting (remove markdown ```json and ``` ticks if present)
                $cleanedContent = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
                $cleanedContent = preg_replace('/\s*```$/', '', $cleanedContent);
                
                $extractedData = json_decode($cleanedContent, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    OcrLog::create([
                        'file_path' => basename($filePath),
                        'response_data' => $responseData,
                        'extracted_fields' => $extractedData,
                        'user_id' => $userId,
                        'status' => 'Success'
                    ]);

                    return [
                        'success' => true,
                        'data' => $extractedData
                    ];
                } else {
                    Log::error('OCR JSON parsing error: ' . json_last_error_msg() . ' Content was: ' . $content);
                }
            }

            OcrLog::create([
                'file_path' => basename($filePath),
                'response_data' => $responseData ?: ['raw' => $response->body()],
                'extracted_fields' => null,
                'user_id' => $userId,
                'status' => 'Failed'
            ]);

            return [
                'success' => false,
                'error' => 'Failed to parse JSON response from OCR model. Raw response logged.'
            ];
        } catch (\Exception $e) {
            Log::error('OCR Service Exception: ' . $e->getMessage());

            OcrLog::create([
                'file_path' => basename($filePath),
                'response_data' => ['error' => $e->getMessage()],
                'extracted_fields' => null,
                'user_id' => $userId,
                'status' => 'Failed'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Provide mock fields for testing.
     */
    private function getMockData(string $type): array
    {
        return [
            'surname' => 'Okeke',
            'first_name' => 'Chidi',
            'other_name' => 'John',
            'date_of_birth' => '2012-05-15',
            'gender' => 'Male',
            'previous_school' => $type === 'pdf' ? 'Ibusa Primary School' : 'St. Thomas Primary School',
            'exam_scores' => 'English: 82/100, Math: 90/100, Science: 85/100'
        ];
    }

    /**
     * Extract scores from a batch entrance exam score sheet for a specific subject.
     */
    public function extractScoresheet(string $filePath, string $mimeType, array $expectedStudents, int $subjectId, int $userId): array
    {
        $apiKey = Setting::get('openrouter_api_key');
        $model = Setting::get('openrouter_model', 'google/gemini-2.5-flash');

        $text = '';
        $isPdf = str_contains($mimeType, 'pdf');

        if ($isPdf) {
            try {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($filePath);
                $text = $pdf->getText();
            } catch (\Exception $e) {
                Log::warning('PDF text extraction failed: ' . $e->getMessage());
            }
        }

        // Get subject name for better OCR matching
        $subject = \App\Models\ExamSubject::find($subjectId);
        $subjectName = $subject ? $subject->name : "Subject $subjectId";

        if (empty($apiKey)) {
            return [
                'success' => false,
                'error' => 'OpenRouter API key is not configured. Cannot process scoresheet OCR without a valid API key.'
            ];
        }
        
        $prompt = "Analyze the uploaded entrance exam score sheet document/image and extract the exam scores for the $subjectName subject.

CRITICAL INSTRUCTIONS:
1. Look for REGISTRATION NUMBERS on the sheet (they may be labeled as: Registration No, Reg No, Student ID, Admission No, ID Number, etc.)
2. Find the corresponding $subjectName SCORE next to each registration number
3. Return ONLY a valid raw JSON object mapping registration numbers to their scores

Return format:
{
  \"SAC-0001\": 85,
  \"SAC-0002\": 92,
  \"SAC-0003\": 78
}

IMPORTANT:
- Extract ONLY pairs of (registration_number, score) that BOTH exist on the sheet
- If a registration number has no score, skip it completely (do not include in response)
- If a score has no registration number, skip it (do not include in response)
- Do NOT make up or guess any data
- Return ONLY valid JSON with registration numbers as keys and scores as values
- No markdown blocks (no ```json), no explanations, no other text";

        $messages = [];
        if ($isPdf && !empty($text)) {
            $messages[] = [
                'role' => 'user',
                'content' => $prompt . "\n\nDocument Text:\n" . $text
            ];
        } else {
            $fileData = base64_encode(file_get_contents($filePath));
            $dataUrl = 'data:' . $mimeType . ';base64,' . $fileData;

            $messages[] = [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $prompt
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $dataUrl
                        ]
                    ]
                ]
            ];
        }

        try {
            $response = Http::timeout(45)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'HTTP-Referer' => config('app.url', 'http://localhost'),
                    'X-Title' => "St. Augustine's College Admission Portal"
                ])
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.1
                ]);

            $responseData = $response->json();
            
            if ($response->successful() && isset($responseData['choices'][0]['message']['content'])) {
                $content = $responseData['choices'][0]['message']['content'];
                
                $cleanedContent = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
                $cleanedContent = preg_replace('/\s*```$/', '', $cleanedContent);
                
                $extractedData = json_decode($cleanedContent, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($extractedData)) {
                    // Filter: Only keep valid entries (registration_number => score)
                    $finalData = [];
                    foreach ($extractedData as $regNo => $score) {
                        // Validate: score must be numeric and between 0-100
                        if (is_numeric($score) && $score >= 0 && $score <= 100) {
                            $finalData[$regNo] = (int)$score;
                        }
                    }

                    OcrLog::create([
                        'file_path' => basename($filePath) . ' (Scoresheet - ' . $subjectName . ')',
                        'response_data' => $responseData,
                        'extracted_fields' => $finalData,
                        'user_id' => $userId,
                        'status' => 'Success'
                    ]);

                    return [
                        'success' => true,
                        'data' => $finalData
                    ];
                } else {
                    Log::error('OCR Scoresheet JSON parsing error: ' . json_last_error_msg() . ' Content: ' . $content);
                }
            }

            OcrLog::create([
                'file_path' => basename($filePath) . ' (Scoresheet - ' . $subjectName . ')',
                'response_data' => $responseData ?: ['raw' => $response->body()],
                'extracted_fields' => null,
                'user_id' => $userId,
                'status' => 'Failed'
            ]);

            return [
                'success' => false,
                'error' => 'Failed to parse JSON response from OCR model.'
            ];
        } catch (\Exception $e) {
            Log::error('OCR Scoresheet Service Exception: ' . $e->getMessage());

            OcrLog::create([
                'file_path' => basename($filePath) . ' (Scoresheet - ' . $subjectName . ')',
                'response_data' => ['error' => $e->getMessage()],
                'extracted_fields' => null,
                'user_id' => $userId,
                'status' => 'Failed'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
