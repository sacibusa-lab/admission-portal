<?php

namespace App\Http\Controllers;

use App\Services\OCRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OcrController extends Controller
{
    protected OCRService $ocrService;

    public function __construct(OCRService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    /**
     * Process uploaded document for OCR data extraction.
     */
    public function process(Request $request)
    {
        // Security: Validate uploads (file type and size limit)
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpeg,jpg,png|max:5120', // Max 5MB
        ]);

        $file = $request->file('document');
        $filePath = $file->getRealPath();
        $mimeType = $file->getMimeType();

        // Trigger OCR extraction
        $result = $this->ocrService->extract($filePath, $mimeType, Auth::id());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'mock' => $result['mock'] ?? false
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Document text extraction failed.'
        ], 422);
    }

    /**
     * Process uploaded score sheet document.
     */
    public function processScoresheet(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpeg,jpg,png|max:5120', // Max 5MB
            'expected_students' => 'required|string', // JSON encoded array
            'expected_subjects' => 'required|string', // JSON encoded array
        ]);

        $file = $request->file('document');
        $filePath = $file->getRealPath();
        $mimeType = $file->getMimeType();
        
        $expectedStudents = json_decode($request->expected_students, true);
        if (!is_array($expectedStudents)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid expected students format.'
            ], 400);
        }

        $expectedSubjects = json_decode($request->expected_subjects, true);
        if (!is_array($expectedSubjects)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid expected subjects format.'
            ], 400);
        }

        $result = $this->ocrService->extractScoresheet($filePath, $mimeType, $expectedStudents, $expectedSubjects, Auth::id());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'mock' => $result['mock'] ?? false
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Score sheet text extraction failed.'
        ], 422);
    }
}
