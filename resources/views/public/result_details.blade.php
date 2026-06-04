@extends('layouts.public')

@section('title', 'Result Details')

@section('styles')
<style>
    /* Premium visual styling for details */
    .profile-photo-container {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    @media print {
        /* Hide navbar, footer, back button, print button, letter download buttons */
        .navbar, .navbar-public, .footer-public, .d-print-none, .btn, .border-top {
            display: none !important;
        }

        body {
            background-color: #ffffff !important;
            color: #000000 !important;
            font-family: Arial, sans-serif !important;
            margin: 0 !important;
            padding: 10px !important;
        }

        .container {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .row {
            display: block !important;
        }

        .col-12, .col-lg-8 {
            width: 100% !important;
            max-width: 100% !important;
            flex: none !important;
        }

        .glass-card {
            box-shadow: none !important;
            border: 2px solid #cbd5e1 !important;
            border-radius: 0 !important;
            background: transparent !important;
            padding: 0 !important;
        }

        .card-header {
            background-color: #0f172a !important;
            color: #ffffff !important;
            border-radius: 0 !important;
            border-bottom: 2px solid #333333 !important;
            padding: 20px !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .card-body {
            padding: 20px !important;
        }

        .table-light {
            background-color: #f1f5f9 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .badge-status {
            border: 1px solid #334155 !important;
            color: #334155 !important;
            background-color: transparent !important;
            font-weight: bold !important;
            padding: 4px 8px !important;
            border-radius: 4px !important;
        }
    }
</style>
@endsection

@section('content')
@php
    $hasScores = $applicant->examScores->isNotEmpty();
    $avgScore = $hasScores ? round($applicant->examScores->avg('score'), 1) : null;
    
    $class = strtoupper($applicant->class_applying_for);
    $isJunior = str_starts_with($class, 'JSS') || str_contains($class, 'JUNIOR');
    $isSenior = str_starts_with($class, 'SS') || str_contains($class, 'SENIOR');
    $juniorCutoff = intval(\App\Models\Setting::get('admission_junior_cutoff', 50));
    $seniorCutoff = intval(\App\Models\Setting::get('admission_senior_cutoff', 50));
    $cutoff = $isJunior ? $juniorCutoff : ($isSenior ? $seniorCutoff : 50);
    
    $examPassed = $hasScores && ($avgScore >= $cutoff);
    $examFailed = $hasScores && ($avgScore < $cutoff);

    // Determine the final status to display
    $displayStatus = $applicant->admission_status;
    
    if ($applicant->admission_status === 'Rejected') {
        $displayStatus = 'Rejected';
        $badgeClass = 'badge-rejected';
        $theme = 'danger';
    } elseif ($examFailed) {
        // If the candidate failed the exam, they must see Failed, even if their DB status 
        // was stuck on 'Admitted' from a previous auto-admission before scores changed.
        $displayStatus = 'Failed';
        $badgeClass = 'badge-failed';
        $theme = 'danger';
    } elseif ($examPassed || $applicant->admission_status === 'Admitted' || $applicant->admission_status === 'Passed') {
        $displayStatus = 'Admitted';
        $badgeClass = 'badge-admitted';
        $theme = 'success';
    } elseif ($applicant->admission_status === 'Failed') {
        $displayStatus = 'Failed';
        $badgeClass = 'badge-failed';
        $theme = 'danger';
    } else {
        // For other status (e.g. Pending, Under Review, Exam Scheduled, Exam Written)
        $displayStatus = $applicant->admission_status;
        if ($displayStatus === 'Under Review') {
            $badgeClass = 'badge-review';
            $theme = 'primary';
        } elseif ($displayStatus === 'Exam Scheduled') {
            $badgeClass = 'badge-exam-sch';
            $theme = 'warning';
        } elseif ($displayStatus === 'Exam Written') {
            $badgeClass = 'badge-exam-writ';
            $theme = 'info';
        } else {
            $badgeClass = 'badge-pending';
            $theme = 'secondary';
        }
    }
@endphp
<div class="row justify-content-center animate__animated animate__fadeIn" style="margin-top: 1rem; margin-bottom: 3rem;">
    <div class="col-12 col-lg-8">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="glass-card">
            <!-- Header Section -->
            <div class="card-header bg-dark text-white p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold m-0">Entrance Exam Result Sheet</h4>
                    <small class="text-white-50">Academic Session: {{ $applicant->academicSession->name }}</small>
                </div>
                <div>
                    <span class="badge-status {{ $badgeClass }}">
                        Status: {{ $displayStatus }}
                    </span>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">
                <!-- Personal Info Grid with Passport Photo -->
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-person-fill text-primary me-2"></i>Candidate Profile</h5>
                <div class="row align-items-center mb-4">
                    <!-- Passport Image (Col 3 on large screens) -->
                    <div class="col-12 col-md-3 text-center mb-3 mb-md-0 order-md-2 profile-photo-container">
                        @if($applicant->passport_path)
                            <img src="{{ $applicant->passport_url }}" alt="Passport" class="rounded border border-3 border-light shadow-sm img-thumbnail" style="width: 130px; height: 130px; object-fit: cover;">
                        @else
                            <div class="rounded border border-3 border-light shadow-sm bg-light text-muted d-inline-flex align-items-center justify-content-center" style="width: 130px; height: 130px;">
                                <i class="bi bi-person-fill fs-1" style="font-size: 3.5rem !important;"></i>
                            </div>
                        @endif
                    </div>

                    <!-- Details list (Col 9 on large screens) -->
                    <div class="col-12 col-md-9 order-md-1">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="p-2 border-bottom border-light">
                                    <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.75rem;">Full Name</span>
                                    <span class="fw-bold text-dark text-uppercase fs-5">{{ $applicant->full_name }}</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="p-2 border-bottom border-light">
                                    <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.75rem;">Registration Number</span>
                                    <span class="fw-bold text-primary fs-5">{{ $applicant->registration_number }}</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="p-2 border-bottom border-light">
                                    <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.75rem;">Class Applied For</span>
                                    <span class="fw-semibold text-dark">{{ $applicant->class_applying_for }}</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="p-2 border-bottom border-light">
                                    <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.75rem;">Parent Phone Number</span>
                                    <span class="fw-semibold text-dark">{{ $applicant->parent_phone_number }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scores Section -->
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2 mt-4"><i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i>Entrance Exam Scores</h5>
                
                @if($applicant->examScores->isNotEmpty())
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 px-4">Subject</th>
                                    <th class="py-3 px-4 text-center" style="width: 150px;">Score (100)</th>
                                    <th class="py-3 px-4 text-center" style="width: 150px;">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($applicant->examScores as $score)
                                    <tr>
                                        <td class="py-3 px-4 fw-semibold">{{ $score->subject->name }}</td>
                                        <td class="py-3 px-4 text-center fw-bold fs-5 {{ $score->score >= 50 ? 'text-success' : 'text-danger' }}">
                                            {{ $score->score }}
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            @if($score->score >= 75)
                                                <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1 small fw-semibold">Excellent</span>
                                            @elseif($score->score >= 50)
                                                <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 px-2 py-1 small fw-semibold">Pass</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 px-2 py-1 small fw-semibold">Fail</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="table-light">
                                    <td class="py-3 px-4 fw-bold">Average Score</td>
                                    @php
                                        $avgScore = round($applicant->examScores->avg('score'), 1);
                                    @endphp
                                    <td class="py-3 px-4 text-center fw-bold fs-4 {{ $avgScore >= $cutoff ? 'text-success' : 'text-danger' }}">
                                        {{ $avgScore }}%
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($avgScore >= $cutoff)
                                            <span class="badge bg-success px-3 py-2 fw-semibold">PASSED</span>
                                        @else
                                            <span class="badge bg-danger px-3 py-2 fw-semibold">FAILED</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning border-0 shadow-sm mb-4 py-3 d-flex align-items-center">
                        <i class="bi bi-clock-history me-3 fs-3"></i>
                        <div>
                            <strong class="d-block mb-1">Exam Scores Pending</strong>
                            Exam scores are currently not available or have not been updated. Please verify again later.
                        </div>
                    </div>
                @endif

                <!-- Dynamic feedback based on admission status -->
                <div class="mt-4 p-4 rounded-3 
                    @if($theme === 'success') bg-success bg-opacity-10 border border-success border-opacity-25
                    @elseif($theme === 'danger') bg-danger bg-opacity-10 border border-danger border-opacity-25
                    @else bg-primary bg-opacity-10 border border-primary border-opacity-25
                    @endif">
                    <h5 class="fw-bold mb-2
                        @if($theme === 'success') text-success
                        @elseif($theme === 'danger') text-danger
                        @else text-primary
                        @endif">
                        <i class="bi 
                            @if($theme === 'success') bi-patch-check-fill
                            @elseif($theme === 'danger') bi-patch-exclamation-fill
                            @else bi-info-square-fill
                            @endif me-2"></i>
                        Admissions Office Decision
                    </h5>
                    <p class="mb-0 text-dark" style="font-size: 0.95rem; line-height: 1.6;">
                        @if($displayStatus === 'Admitted' || $displayStatus === 'Passed')
                            Congratulations! Your admission into St. Augustine's College has been approved. You are now officially offered a place. Please click the button below to download your official Admission Letter.
                        @elseif($displayStatus === 'Rejected')
                            We regret to inform you that you have not been offered admission for this academic session. We wish you success in your future academic endeavors.
                        @elseif($displayStatus === 'Failed')
                            We regret to inform you that you did not meet the minimum score requirement of {{ $cutoff }}% for admission.
                        @elseif($displayStatus === 'Exam Scheduled')
                            Your entrance examination has been scheduled. Please ensure you print your registration slip and prepare accordingly.
                        @elseif($displayStatus === 'Exam Written')
                            Your entrance examination is complete. Results are being compiled and verified.
                        @elseif($displayStatus === 'Under Review')
                            Your application is currently under review by our admissions department.
                        @else
                            Your application status is currently pending. Please check back soon.
                        @endif
                    </p>
                </div>

                <!-- Oral Interview Invitation Panel (For Admitted Candidates) -->
                @if($displayStatus === 'Admitted')
                    <div class="alert alert-info border-0 shadow-sm mt-4 p-4 d-flex align-items-start gap-3">
                        <div class="bg-info bg-opacity-10 text-info p-2 rounded-3 d-inline-flex">
                            <i class="bi bi-calendar-check-fill fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Oral Interview Invitation</h5>
                            <p class="mb-2 text-muted" style="font-size: 0.92rem;">You are required to attend an oral interview as part of the final registration process.</p>
                            <div class="mb-2">
                                <span class="fw-semibold text-secondary">Date & Time:</span> 
                                <span class="fw-bold text-primary">{{ \App\Models\Setting::get('admission_interview_date', 'Saturday, July 18, 2026') }}</span>
                            </div>
                            <div>
                                <span class="fw-semibold text-secondary">Venue:</span> 
                                <span class="fw-semibold text-dark">School Main Assembly Hall, St. Augustine's College, Ibusa</span>
                            </div>
                            <div class="mt-2 text-muted" style="font-size: 0.8rem;">
                                Please bring printed copies of your application slip, entrance exam result sheet, birth certificate, and previous school reports.
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Call to action button panel -->
                <div class="mt-4 pt-3 border-top border-light d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 d-print-none">
                    <a href="{{ route('public.results.form') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2 w-100 w-sm-auto">
                        <i class="bi bi-arrow-left"></i> Check Another Result
                    </a>
                    
                    <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto align-items-center">
                        @if($displayStatus === 'Failed')
                            <form action="{{ route('public.results.resit', $applicant->id) }}" method="POST" class="m-0 w-100 w-sm-auto" onsubmit="return confirm('Are you sure you want to register for a resit exam? Your current scores will be cleared, and you will be assigned to a new resit batch.')">
                                @csrf
                                <button type="submit" class="btn btn-danger d-flex align-items-center justify-content-center gap-2 py-2 px-4 fw-bold w-100">
                                    <i class="bi bi-arrow-repeat"></i> Register for Resit
                                </button>
                            </form>
                        @endif

                        <button onclick="window.print()" class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 py-2 px-3 fw-bold w-100 w-sm-auto">
                            <i class="bi bi-printer-fill"></i> Print Result
                        </button>
                        
                        @if($displayStatus === 'Admitted')
                            <a href="{{ route('public.results.letter', $applicant->id) }}" class="btn btn-success d-flex align-items-center justify-content-center gap-2 py-2 px-4 fw-bold w-100 w-sm-auto">
                                <i class="bi bi-file-earmark-pdf-fill"></i> Download Admission Letter
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
