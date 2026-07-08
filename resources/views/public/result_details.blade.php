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

        .col-lg-8 {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
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

{{-- ── Batch Selector Overlay ── --}}
@if(!empty($availableBatches) && empty($selectedBatch))
<div class="row justify-content-center animate__animated animate__fadeIn" style="margin-top: 2rem; margin-bottom: 2rem;">
    <div class="col-12 col-md-8 col-lg-5">
        <div class="glass-card p-4 p-md-5 text-center">
            <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle mb-3" style="width: 72px; height: 72px;">
                <i class="bi bi-layers fs-1"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Select Exam Batch</h3>
            <p class="text-muted mb-4">You have results from multiple exam sessions. Please select which batch you'd like to view.</p>

            <form action="{{ route('public.results.select-batch') }}" method="POST">
                @csrf
                <div class="d-flex flex-column gap-3">
                    @foreach($availableBatches as $batch)
                        <button type="submit" name="batch" value="{{ $batch }}" 
                                class="btn btn-outline-primary btn-lg fw-semibold d-flex align-items-center justify-content-center gap-2 py-3">
                            <i class="bi bi-file-text"></i> {{ $batch }}
                        </button>
                    @endforeach
                </div>
            </form>

            <hr class="my-4 text-muted opacity-25">
            <a href="{{ route('public.results.form') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>
@else

@php
    $hasScores = $applicant->examScores->isNotEmpty();
    $avgScore = $hasScores ? round($applicant->examScores->avg('score'), 1) : null;
    
    $class = strtoupper($applicant->class_applying_for);
    $isJunior = str_starts_with($class, 'JSS') || str_contains($class, 'JUNIOR');
    $isSenior = str_starts_with($class, 'SS') || str_contains($class, 'SENIOR');
    $juniorCutoff = intval(\App\Models\Setting::get('admission_junior_cutoff', 50));
    $seniorCutoff = intval(\App\Models\Setting::get('admission_senior_cutoff', 50));
    $cutoff = $isJunior ? $juniorCutoff : ($isSenior ? $seniorCutoff : 50);
    
    $isResitCandidate = $applicant->exam_batch && str_contains($applicant->exam_batch, 'Resit');
    $examPassed = $hasScores && ($avgScore >= $cutoff);
    $examFailed = $hasScores && ($avgScore < $cutoff);

    // Determine the final status to display
    $displayStatus = $applicant->admission_status;
    
    if ($isResitCandidate) {
        // Resit candidates keep their current status (Pending) and don't get
        // overridden by filtered scores (which only show failed subjects)
        $displayStatus = $applicant->admission_status;
        if ($displayStatus === 'Pending') {
            $badgeClass = 'badge-pending';
            $theme = 'secondary';
        } else {
            $badgeClass = 'badge-failed';
            $theme = 'danger';
        }
    } elseif ($applicant->admission_status === 'Rejected') {
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

    // Resolve batch-specific interview instructions
    $batch = $applicant->exam_batch ?: 'Batch A';
    $batchSlug = \Illuminate\Support\Str::slug($batch);
    $interviewDate = \App\Models\Setting::get("interview_date_{$batchSlug}") ?: \App\Models\Setting::get('admission_interview_date', 'Saturday, July 18, 2026');
    $interviewVenue = \App\Models\Setting::get("interview_venue_{$batchSlug}") ?: "School Main Assembly Hall, St. Augustine's College, Ibusa";
    $interviewInstructions = \App\Models\Setting::get("interview_instructions_{$batchSlug}") ?: "Please bring printed copies of your application slip, entrance exam result sheet, birth certificate, and previous school reports.";
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

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="glass-card">
            <div class="card-body p-4 p-md-5">
                <!-- Official School Letterhead Header -->
                <div class="border-bottom pb-4 mb-4">
                    <div class="row align-items-center justify-content-between g-3">
                        <!-- Left: School Crest Logo -->
                        <div class="col-3 col-md-2 text-center text-md-start">
                            @if(\App\Models\Setting::get('school_logo'))
                                <img src="{{ asset(\App\Models\Setting::get('school_logo')) }}" alt="School Logo" style="height: 100px; width: 100px; object-fit: contain;">
                            @else
                                <!-- Fallback placeholder crest if no logo uploaded -->
                                <div class="d-inline-flex align-items-center justify-content-center bg-light text-primary rounded-circle border border-2 border-primary mx-auto" style="width: 100px; height: 100px;">
                                    <i class="bi bi-mortarboard-fill fs-1"></i>
                                </div>
                            @endif
                        </div>

                        <!-- Center: School Information -->
                        <div class="col-6 col-md-8 text-center">
                            <h6 class="text-uppercase fw-semibold text-secondary mb-1" style="font-size: 0.85rem; letter-spacing: 1px;">
                                Catholic Diocese of Issele-Uku
                            </h6>
                            <h2 class="fw-extrabold text-dark m-0 text-uppercase" style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.8rem; letter-spacing: -0.5px;">
                                {{ \App\Models\Setting::get('school_name', "St. Augustine's College") }}
                            </h2>
                            <p class="fw-semibold text-dark mb-1" style="font-size: 1.05rem;">
                                {{ \App\Models\Setting::get('school_address', 'Ibusa, Delta State, Nigeria') }}
                            </p>
                            <p class="text-muted m-0" style="font-size: 0.82rem; font-weight: 500;">
                                Tel: {{ \App\Models\Setting::get('school_phone', 'xxx-xxx-xxxx') }} &bull; Email: {{ \App\Models\Setting::get('school_email', 'info@saci.com.ng') }}
                            </p>
                        </div>

                        <!-- Right: Candidate Passport Photo -->
                        <div class="col-3 col-md-2 text-center text-md-end">
                            <div class="d-inline-block p-1 bg-white border border-secondary border-opacity-25 rounded shadow-sm" style="width: 110px; height: 110px;">
                                @if($applicant->passport_path)
                                    <img src="{{ $applicant->passport_url }}" alt="Passport" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                                @else
                                    <div class="w-100 h-100 bg-light text-muted d-flex flex-column align-items-center justify-content-center" style="border-radius: 4px;">
                                        <i class="bi bi-person-fill fs-2 mb-0"></i>
                                        <small style="font-size: 0.65rem; font-weight: 600; text-transform: uppercase;">Photo</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Result Sheet Title & Status Badge Bar -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center bg-light border rounded p-3 mb-4 gap-2">
                    <div>
                        <h5 class="fw-bold text-dark m-0">Entrance Exam Result Sheet</h5>
                        <small class="text-muted">Academic Session: {{ $applicant->academicSession->name }}</small>
                    </div>
                    <div>
                        <span class="badge-status {{ $badgeClass }} py-2 px-3 fs-6">
                            Status: {{ $displayStatus }}
                        </span>
                    </div>
                </div>

                <!-- Personal Info Grid -->
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-person-fill text-primary me-2"></i>Candidate Profile</h5>
                <div class="row g-3 mb-4">
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
                    @if(!empty($selectedBatch))
                    <div class="col-12">
                        <div class="p-2 bg-warning bg-opacity-10 rounded-3">
                            <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.75rem;">
                                <i class="bi bi-layers me-1"></i> Viewing Batch
                            </span>
                            <span class="fw-bold text-dark">{{ $selectedBatch }}</span>
                            <a href="{{ route('public.results.select-batch') }}?show=1" class="btn btn-sm btn-outline-warning ms-3" onclick="event.preventDefault(); document.getElementById('resetBatchForm').submit();">
                                <i class="bi bi-arrow-repeat"></i> Switch Batch
                            </a>
                            <form id="resetBatchForm" action="{{ route('public.results.select-batch') }}" method="POST" class="d-none">
                                @csrf
                                <input type="hidden" name="batch" value="">
                            </form>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Scores Section -->
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2 mt-4">
                    <i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i>Entrance Exam Scores
                    @if($isResitCandidate)
                        <span class="badge bg-warning text-dark ms-2 px-3 py-2" style="font-size: 0.75rem;">
                            <i class="bi bi-arrow-repeat me-1"></i> Resit — Failed Subjects Only
                        </span>
                    @endif
                </h5>

                @if($isResitCandidate && $applicant->examScores->isNotEmpty())
                    <div class="alert alert-warning border-0 shadow-sm mb-4 py-3 d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                        <div>
                            <strong class="d-block mb-1">Resit Examination Results</strong>
                            Only subjects you failed in the previous exam are shown below. Subjects you passed (score &ge; 50) are considered completed and do not need to be retaken.
                        </div>
                    </div>
                @endif
                
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
                                @php
                                    $totalScore = $applicant->examScores->sum('score');
                                    $avgScore = round($applicant->examScores->avg('score'), 1);
                                    $subjectsCount = $applicant->examScores->count();
                                @endphp
                                <tr class="table-light border-bottom-0">
                                    <td class="py-3 px-4 fw-bold">Total Score</td>
                                    <td class="py-3 px-4 text-center fw-bold fs-5 text-dark">
                                        {{ $totalScore }} <small class="text-muted fw-normal">/ {{ $subjectsCount * 100 }}</small>
                                    </td>
                                    <td class="py-3 px-4 text-center"></td>
                                </tr>
                                <tr class="table-light border-top-0">
                                    <td class="py-3 px-4 fw-bold">Average Score</td>
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
                                <span class="fw-bold text-primary">{{ $interviewDate }}</span>
                            </div>
                            <div>
                                <span class="fw-semibold text-secondary">Venue:</span> 
                                <span class="fw-semibold text-dark">{{ $interviewVenue }}</span>
                            </div>
                            <div class="mt-2 text-muted" style="font-size: 0.8rem;">
                                {{ $interviewInstructions }}
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
                        @if($displayStatus === 'Failed' && !str_contains($applicant->exam_batch ?? '', 'Resit'))
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
@endif
@endsection
