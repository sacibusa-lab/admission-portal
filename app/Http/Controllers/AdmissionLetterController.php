<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Setting;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class AdmissionLetterController extends Controller
{
    /**
     * Preview and print-friendly view of the admission letter.
     */
    public function show($id)
    {
        $applicant = Applicant::with(['academicSession', 'examScores'])->findOrFail($id);

        if (!$applicant->passesCutoff()) {
            return redirect()->back()->with('error', 'Admission letters can only be generated for candidates who meet the cutoff mark.');
        }

        // Fetch template and replace placeholders
        $template = Setting::get('admission_letter_template');
        $letterContent = str_replace(
            ['{firstname}', '{surname}', '{registration_number}', '{class}', '{session}'],
            [
                $applicant->first_name,
                $applicant->surname,
                $applicant->registration_number,
                $applicant->class_applying_for,
                $applicant->academicSession->name
            ],
            $template
        );

        $schoolName = Setting::get('school_name', "St. Augustine's College, Ibusa");
        $schoolAddress = Setting::get('school_address', 'Ibusa, Delta State, Nigeria');
        $schoolEmail = Setting::get('school_email', 'info@staugustineibusa.com');
        $schoolPhone = Setting::get('school_phone', '+2348030000000');

        return view('letters.show', compact('applicant', 'letterContent', 'schoolName', 'schoolAddress', 'schoolEmail', 'schoolPhone'));
    }

    /**
     * Export the admission letter as a PDF download.
     */
    public function downloadPdf($id)
    {
        $applicant = Applicant::with(['academicSession', 'examScores'])->findOrFail($id);

        if (!$applicant->passesCutoff()) {
            return redirect()->back()->with('error', 'Admission letters can only be generated for candidates who meet the cutoff mark.');
        }

        $template = Setting::get('admission_letter_template');
        $letterContent = str_replace(
            ['{firstname}', '{surname}', '{registration_number}', '{class}', '{session}'],
            [
                $applicant->first_name,
                $applicant->surname,
                $applicant->registration_number,
                $applicant->class_applying_for,
                $applicant->academicSession->name
            ],
            $template
        );

        $schoolName = Setting::get('school_name', "St. Augustine's College, Ibusa");
        $schoolAddress = Setting::get('school_address', 'Ibusa, Delta State, Nigeria');
        $schoolEmail = Setting::get('school_email', 'info@staugustineibusa.com');
        $schoolPhone = Setting::get('school_phone', '+2348030000000');

        // Compile HTML to render in Dompdf
        $html = view('letters.pdf', compact('applicant', 'letterContent', 'schoolName', 'schoolAddress', 'schoolEmail', 'schoolPhone'))->render();

        $pdf = Pdf::loadHTML($html);
        
        AuditLogger::log('download_admission_letter', [
            'applicant_id' => $applicant->id,
            'registration_number' => $applicant->registration_number
        ]);

        return $pdf->download("admission_letter_{$applicant->registration_number}.pdf");
    }
}
