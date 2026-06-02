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
     * Extract scores from a batch entrance exam score sheet.
     */
    public function extractScoresheet(string $filePath, string $mimeType, array $expectedStudents, array $expectedSubjects, int $userId): array
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

        if (empty($apiKey)) {
            // Mock Mode: Generate plausible scores (e.g. between 35 and 95) for the expected students and subjects
            $mockData = [];
            foreach ($expectedStudents as $student) {
                if (isset($student['registration_number'])) {
                    $regNo = $student['registration_number'];
                    $mockData[$regNo] = [];
                    foreach ($expectedSubjects as $subject) {
                        $subId = $subject['id'];
                        $pseudoRandomScore = (crc32($regNo . '_' . $subId) % 50) + 45; // Generates scores between 45 and 95
                        $mockData[$regNo][$subId] = $pseudoRandomScore;
                    }
                }
            }

            OcrLog::create([
                'file_path' => basename($filePath) . ' (Scoresheet)',
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

        $expectedStudentsJson = json_encode($expectedStudents, JSON_PRETTY_PRINT);
        $expectedSubjectsJson = json_encode($expectedSubjects, JSON_PRETTY_PRINT);
        
        $prompt = "Analyze the uploaded entrance exam score sheet document/image and extract the exam scores for each of the listed candidates across the specified subjects.
Here is the list of expected candidates (with their names and registration numbers):
{$expectedStudentsJson}

Here is the list of expected subjects (with their database IDs and names):
{$expectedSubjectsJson}

Match the names/records in the score sheet with the candidates and subjects. 
Return ONLY a valid raw JSON object. The keys must be the candidate's registration number (e.g., \"SAC-0001\"), and the value must be an object mapping the subject ID (e.g. \"1\", \"2\") to their corresponding exam score (an integer between 0 and 100).
Example format:
{
  \"SAC-0001\": {
    \"1\": 85,
    \"2\": 70
  }
}
If a candidate has no score recorded for a subject, do not include that subject key or set its value to null.
Do NOT wrap the JSON response in markdown blocks (no ```json). No other text, comments, or explanations.";

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

                if (json_last_error() === JSON_ERROR_NONE) {
                    OcrLog::create([
                        'file_path' => basename($filePath) . ' (Scoresheet)',
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
                    Log::error('OCR Scoresheet JSON parsing error: ' . json_last_error_msg() . ' Content: ' . $content);
                }
            }

            OcrLog::create([
                'file_path' => basename($filePath) . ' (Scoresheet)',
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
                'file_path' => basename($filePath) . ' (Scoresheet)',
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
