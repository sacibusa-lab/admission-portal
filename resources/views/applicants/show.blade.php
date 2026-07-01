@extends('layouts.app')

@section('title', 'Applicant Profile')

@section('content')
<div class="container-fluid p-0">
    <!-- Header Back Navigation -->
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('applicants.index') }}" class="btn btn-outline-secondary btn-sm rounded-circle p-2" style="width: 32px; height: 32px; line-height: 12px;" title="Back to list">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span class="text-muted">Back to Applicants List</span>
    </div>

    <!-- Profile Header Card -->
    <div class="card shadow-sm border-0 mb-4 overflow-hidden">
        <div class="card-body p-4 bg-dark text-white position-relative" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                <!-- Photo Thumbnail -->
                <div>
                    @if($applicant->passport_path)
                        <img src="{{ $applicant->passport_url }}" alt="Passport" class="rounded border border-3 border-light shadow" style="width: 120px; height: 120px; object-fit: cover;">
                    @else
                        <div class="bg-light text-muted rounded border border-3 border-light shadow d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                            <i class="bi bi-person-fill fs-1"></i>
                        </div>
                    @endif
                </div>

                <!-- Info Block -->
                <div class="text-center text-md-start">
                    <span class="badge bg-warning text-dark mb-2 px-2 py-1" style="font-size: 0.72rem; font-weight: 600;">{{ $applicant->class_applying_for }} OFFER</span>
                    <h3 class="fw-bold m-0 text-white">{{ $applicant->full_name }}</h3>
                    <p class="text-secondary mt-1 mb-2">{{ $applicant->registration_number }} &bull; Session: {{ $applicant->academicSession->name }} &bull; Batch: {{ $applicant->exam_batch }}</p>
                    
                    <span class="badge 
                        @if($applicant->admission_status === 'Pending') badge-pending
                        @elseif($applicant->admission_status === 'Under Review') badge-review
                        @elseif($applicant->admission_status === 'Exam Scheduled') badge-exam-sch
                        @elseif($applicant->admission_status === 'Exam Written') badge-exam-writ
                        @elseif($applicant->admission_status === 'Passed') badge-passed
                        @elseif($applicant->admission_status === 'Failed') badge-failed
                        @elseif($applicant->admission_status === 'Admitted') badge-admitted
                        @elseif($applicant->admission_status === 'Rejected') badge-rejected
                        @endif px-3 py-2 border border-secondary border-opacity-10" style="font-size: 0.82rem;">
                        Status: {{ $applicant->admission_status }}
                    </span>
                </div>

                <!-- Action Button in Header -->
                <div class="ms-md-auto text-center text-md-end">
                    <div class="d-flex flex-wrap justify-content-center justify-content-md-end gap-2">
                        @if(auth()->user()->hasPermission('register_applicants'))
                            <a href="{{ route('applicants.edit', $applicant->id) }}" class="btn btn-outline-light d-flex align-items-center gap-2">
                                <i class="bi bi-pencil-fill"></i> Edit Profile
                            </a>
                            <form action="{{ route('applicants.destroy', $applicant->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this applicant profile? This action will archive/soft-delete their record.');" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger d-flex align-items-center gap-2">
                                    <i class="bi bi-trash-fill"></i> Delete Profile
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('applicants.slip', $applicant->id) }}" target="_blank" class="btn btn-outline-light d-flex align-items-center gap-2">
                            <i class="bi bi-printer"></i> Print Slip
                        </a>
                        @if($applicant->passesCutoff())
                            <a href="{{ route('letters.show', $applicant->id) }}" class="btn btn-warning fw-semibold text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-pdf"></i> Admission Letter
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left: Core Information Tabs -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <!-- Tab Menu -->
                <div class="card-header p-0 bg-light border-bottom">
                    <ul class="nav nav-tabs px-4 pt-3 border-0" id="profileTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold text-secondary px-3 pb-3 border-0" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-pane" type="button" role="tab">
                                <i class="bi bi-person-fill me-1"></i> Personal Profile
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold text-secondary px-3 pb-3 border-0" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs-pane" type="button" role="tab">
                                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Uploaded Documents
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold text-secondary px-3 pb-3 border-0" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline-pane" type="button" role="tab">
                                <i class="bi bi-clock-history me-1"></i> History Timeline
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold text-secondary px-3 pb-3 border-0" id="sms-tab" data-bs-toggle="tab" data-bs-target="#sms-pane" type="button" role="tab">
                                <i class="bi bi-chat-left-dots-fill me-1"></i> SMS Alerts
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold text-secondary px-3 pb-3 border-0" id="exams-tab" data-bs-toggle="tab" data-bs-target="#exams-pane" type="button" role="tab">
                                <i class="bi bi-award-fill me-1"></i> Exam Results
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-4">
                    <div class="tab-content" id="profileTabContent">
                        <!-- Profile Info Tab -->
                        <div class="tab-pane fade show active" id="info-pane" role="tabpanel" aria-labelledby="info-tab">
                            <div class="row g-4">
                                <!-- Personal info -->
                                <div class="col-12">
                                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Personal & Origin Details</h6>
                                    <div class="row g-3">
                                        <div class="col-6 col-md-4">
                                            <span class="text-muted d-block" style="font-size: 0.8rem;">Surname:</span>
                                            <strong class="text-dark">{{ $applicant->surname }}</strong>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <span class="text-muted d-block" style="font-size: 0.8rem;">First Name:</span>
                                            <strong class="text-dark">{{ $applicant->first_name }}</strong>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <span class="text-muted d-block" style="font-size: 0.8rem;">Other Name:</span>
                                            <strong class="text-dark">{{ $applicant->other_name ?: 'N/A' }}</strong>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <span class="text-muted d-block" style="font-size: 0.8rem;">Gender:</span>
                                            <strong class="text-dark">{{ $applicant->gender }}</strong>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <span class="text-muted d-block" style="font-size: 0.8rem;">Date of Birth:</span>
                                            <strong class="text-dark">{{ $applicant->date_of_birth->format('d M, Y') }}</strong>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <span class="text-muted d-block" style="font-size: 0.8rem;">Nationality:</span>
                                            <strong class="text-dark">{{ $applicant->nationality }}</strong>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <span class="text-muted d-block" style="font-size: 0.8rem;">State of Origin:</span>
                                            <strong class="text-dark">{{ $applicant->state_of_origin }}</strong>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <span class="text-muted d-block" style="font-size: 0.8rem;">LGA of Origin:</span>
                                            <strong class="text-dark">{{ $applicant->lga }}</strong>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <span class="text-muted d-block" style="font-size: 0.8rem;">Exam Batch:</span>
                                            <strong class="text-dark">{{ $applicant->exam_batch }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact info -->
                                <div class="col-12 mt-4">
                                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Contact Details</h6>
                                    <div class="row g-3">
                                        <div class="col-6 col-md-6">
                                            <span class="text-muted d-block" style="font-size: 0.8rem;">Parent Phone:</span>
                                            <strong class="text-dark">{{ $applicant->parent_phone_number }}</strong>
                                        </div>
                                        <div class="col-6 col-md-6">
                                            <span class="text-muted d-block" style="font-size: 0.8rem;">Email Address:</span>
                                            <strong class="text-dark">{{ $applicant->email ?: 'N/A' }}</strong>
                                        </div>
                                        <div class="col-12">
                                            <span class="text-muted d-block" style="font-size: 0.8rem;">Residential Address:</span>
                                            <strong class="text-dark" style="font-weight: 500;">{{ $applicant->address }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documents Tab -->
                        <div class="tab-pane fade" id="docs-pane" role="tabpanel" aria-labelledby="docs-tab">
                            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Uploaded Registration Files</h6>
                            <div class="row g-3">
                                @forelse($applicant->documents as $doc)
                                    <div class="col-12 col-md-6">
                                        <div class="border rounded p-3 d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-light rounded p-2 text-muted">
                                                    @if(str_contains(strtolower($doc->file_name), 'pdf'))
                                                        <i class="bi bi-filetype-pdf text-danger fs-3" style="line-height: 1;"></i>
                                                    @else
                                                        <i class="bi bi-file-image text-primary fs-3" style="line-height: 1;"></i>
                                                    @endif
                                                </div>
                                                <div>
                                                    <span class="fw-semibold text-dark d-block text-capitalize" style="font-size: 0.9rem;">{{ str_replace('_', ' ', $doc->document_type) }}</span>
                                                    <small class="text-muted" style="font-size: 0.75rem;">{{ round($doc->file_size / 1024) }} KB</small>
                                                </div>
                                            </div>
                                            <a href="{{ $doc->file_url }}" target="_blank" class="btn btn-outline-primary btn-sm px-3 fw-semibold">
                                                View
                                            </a>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-center py-4 text-muted">No documents uploaded.</div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Timeline Tab -->
                        <div class="tab-pane fade" id="timeline-pane" role="tabpanel" aria-labelledby="timeline-tab">
                            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Status Log History</h6>
                            <div class="position-relative ps-4 border-start border-2 border-light ms-2">
                                @foreach($applicant->histories as $history)
                                    <div class="position-relative mb-4">
                                        <!-- Timeline circle marker -->
                                        <span class="position-absolute bg-white rounded-circle border border-3 d-flex align-items-center justify-content-center" style="width: 16px; height: 16px; left: -33px; top: 4px; border-color: var(--primary-color) !important;"></span>
                                        <div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold text-dark">{{ $history->status }}</span>
                                                <small class="text-muted">{{ $history->created_at->format('M d, Y - H:i') }}</small>
                                            </div>
                                            <p class="text-muted m-0 mt-1" style="font-size: 0.88rem;">{{ $history->remarks ?: 'No remarks provided.' }}</p>
                                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Logged by: {{ $history->officer ? $history->officer->name : 'System' }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- SMS Logs Tab -->
                        <div class="tab-pane fade" id="sms-pane" role="tabpanel" aria-labelledby="sms-tab">
                            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Outbound SMS History</h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-2">Date Sent</th>
                                            <th class="py-2">Phone</th>
                                            <th class="py-2">Message</th>
                                            <th class="py-2 text-end">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($smsLogs as $log)
                                            <tr>
                                                <td class="text-muted" style="width: 120px;">{{ $log->created_at->format('M d, H:i') }}</td>
                                                <td>{{ $log->phone }}</td>
                                                <td class="text-truncate text-muted" style="max-width: 250px;" title="{{ $log->message }}">{{ $log->message }}</td>
                                                <td class="text-end">
                                                    <span class="badge {{ str_contains($log->status, 'Sent') ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}"
                                                          title="{{ is_array($log->response) || is_object($log->response) ? json_encode($log->response) : $log->response }}"
                                                          style="cursor: help;">
                                                        {{ $log->status }}
                                                    </span>
                                                    @if(!str_contains($log->status, 'Sent') && $log->response)
                                                        <div class="small text-danger text-end mt-1 text-wrap" style="font-size: 0.72rem; max-width: 250px; word-break: break-word; margin-left: auto;">
                                                            @if(is_array($log->response))
                                                                <strong>Error:</strong> {{ $log->response['error'] ?? $log->response['message'] ?? json_encode($log->response) }}
                                                            @else
                                                                <strong>Error:</strong> {{ $log->response }}
                                                            @endif
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">No SMS notifications logged.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Exam Results Tab -->
                        <div class="tab-pane fade" id="exams-pane" role="tabpanel" aria-labelledby="exams-tab">
                            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Entrance Exam Scores</h6>

                            @php
                                $scores = $applicant->examScores;
                                $average = $scores->count() > 0 ? round($scores->average('score'), 2) : null;
                            @endphp

                            {{-- ── Batch Selector ── --}}
                            @if(!empty($availableBatches))
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 p-3 bg-light rounded-3 border">
                                    <span class="text-muted fw-semibold small me-1">
                                        <i class="bi bi-layers me-1"></i> Filter by batch:
                                    </span>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('applicants.show', $applicant->id) }}"
                                           class="btn btn-sm {{ empty($selectedBatch) ? 'btn-primary' : 'btn-outline-secondary' }}">
                                            All Batches
                                        </a>
                                        @foreach($availableBatches as $batch)
                                            <a href="{{ route('applicants.show', ['id' => $applicant->id, 'batch' => $batch]) }}"
                                               class="btn btn-sm {{ $selectedBatch === $batch ? 'btn-primary' : 'btn-outline-secondary' }}">
                                                {{ $batch }}
                                            </a>
                                        @endforeach
                                    </div>
                                    @if(!empty($selectedBatch))
                                        <span class="badge bg-warning text-dark ms-2 px-3 py-2">
                                            <i class="bi bi-eye me-1"></i> Viewing: {{ $selectedBatch }}
                                        </span>
                                    @endif
                                </div>
                            @endif

                            @if($scores->isNotEmpty())
                                <div class="row g-4 mb-4">
                                    <div class="col-12 col-md-4">
                                        <div class="border rounded p-3 bg-light text-center">
                                            <span class="text-muted d-block" style="font-size: 0.8rem; font-weight: 500;">AVERAGE SCORE</span>
                                            <h2 class="fw-bold text-primary mt-1 mb-0">{{ $average }}%</h2>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <div class="border rounded p-3 bg-light text-center">
                                            <span class="text-muted d-block" style="font-size: 0.8rem; font-weight: 500;">TOTAL SUBJECTS</span>
                                            <h2 class="fw-bold text-dark mt-1 mb-0">{{ $scores->count() }}</h2>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <div class="border rounded p-3 bg-light text-center">
                                            <span class="text-muted d-block" style="font-size: 0.8rem; font-weight: 500;">RESULT STATUS</span>
                                            @if($average >= 50)
                                                <span class="badge bg-success px-3 py-2 mt-2 fs-6">PASSED</span>
                                            @elseif($average !== null)
                                                <span class="badge bg-danger px-3 py-2 mt-2 fs-6">FAILED</span>
                                                @if(auth()->user()->hasRole(['Super Admin', 'Admission Officer']) && !str_contains($applicant->exam_batch ?? '', 'Resit'))
                                                    <form action="{{ route('applicants.resit', $applicant->id) }}" method="POST" class="mt-2" onsubmit="return confirm('Are you sure you want to register this applicant for a resit exam? Existing scores will be cleared.')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger fw-semibold w-100 d-flex align-items-center justify-content-center gap-1">
                                                            <i class="bi bi-arrow-repeat"></i> Register Resit
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary px-3 py-2 mt-2 fs-6">UNGRADED</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="py-2">Subject Name</th>
                                                <th class="py-2 text-end">Score (100)</th>
                                                <th class="py-2 text-end">Status</th>
                                                <th class="py-2 text-end">Batch</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($scores as $score)
                                                <tr>
                                                    <td class="fw-semibold text-dark">{{ $score->subject ? $score->subject->name : 'Unknown' }}</td>
                                                    <td class="text-end fw-bold text-primary">{{ $score->score }}</td>
                                                    <td class="text-end">
                                                        @if($score->score >= 50)
                                                            <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i> Pass</span>
                                                        @else
                                                            <span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> Fail</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end text-muted small">{{ $score->exam_batch ?: $applicant->exam_batch ?: 'N/A' }}</td>
                                                </tr>
                                            @empty
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-award fs-1 d-block mb-3 text-secondary"></i>
                                    No exam scores have been entered for this applicant yet.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- OCR Data Audit Card -->
            @php
                // Fetch OCR logs for auditing
                $ocrLog = App\Models\OcrLog::where('user_id', $applicant->created_by)->orderBy('created_at', 'desc')->first();
            @endphp
            @if($ocrLog)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light">
                        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-shield-lock-fill text-warning me-2"></i>OCR Audit Logs Metadata</h6>
                    </div>
                    <div class="card-body">
                        <span class="text-muted d-block mb-2" style="font-size: 0.8rem;">Document parsed: <code>{{ $ocrLog->file_path }}</code></span>
                        <pre class="bg-dark text-white rounded p-3 m-0" style="font-size: 0.78rem; overflow-x: auto; max-height: 200px;">{{ json_encode($ocrLog->extracted_fields, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Status updates workflow panel -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header">
                    <h5 class="m-0 fw-bold text-dark">Status Progression</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('applicants.status.update', $applicant->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Status</label>
                            <select class="form-select" name="status" required>
                                <option value="Pending" {{ $applicant->admission_status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                <option value="Under Review" {{ $applicant->admission_status === 'Under Review' ? 'selected' : '' }}>Under Review</option>
                                <option value="Exam Scheduled" {{ $applicant->admission_status === 'Exam Scheduled' ? 'selected' : '' }}>Exam Scheduled</option>
                                <option value="Exam Written" {{ $applicant->admission_status === 'Exam Written' ? 'selected' : '' }}>Exam Written</option>
                                <option value="Passed" {{ $applicant->admission_status === 'Passed' ? 'selected' : '' }}>Passed</option>
                                <option value="Failed" {{ $applicant->admission_status === 'Failed' ? 'selected' : '' }}>Failed</option>
                                
                                @if(auth()->user()->hasRole(['Principal', 'Super Admin']))
                                    <option value="Admitted" {{ $applicant->admission_status === 'Admitted' ? 'selected' : '' }}>Admitted</option>
                                    <option value="Rejected" {{ $applicant->admission_status === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                @else
                                    <option value="Admitted" disabled {{ $applicant->admission_status === 'Admitted' ? 'selected' : '' }}>Admitted (Requires Principal approval)</option>
                                    <option value="Rejected" disabled {{ $applicant->admission_status === 'Rejected' ? 'selected' : '' }}>Rejected (Requires Principal approval)</option>
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Workflow Comments</label>
                            <textarea class="form-control" name="remarks" rows="3" placeholder="Enter comments or exam score details..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Apply Transition Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Audit Details Card -->
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <h5 class="m-0 fw-bold text-dark">Profile Metadata</h5>
                </div>
                <div class="card-body" style="font-size: 0.88rem;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Registered by:</span>
                        <strong class="text-dark">{{ $applicant->creator ? $applicant->creator->name : 'System' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Date Registered:</span>
                        <strong class="text-dark">{{ $applicant->created_at->format('jS M Y, H:i') }}</strong>
                    </div>
                    @if($applicant->updater)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Last modified by:</span>
                            <strong class="text-dark">{{ $applicant->updater->name }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Last Modified:</span>
                            <strong class="text-dark">{{ $applicant->updated_at->format('jS M Y, H:i') }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
