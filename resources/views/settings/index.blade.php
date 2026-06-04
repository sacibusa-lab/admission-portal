@extends('layouts.app')

@section('title', 'Portal Settings')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Portal Settings</h3>
            <p class="text-muted m-0">Manage school branding, SMS alerts, OCR configurations, and academic sessions.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Settings Tabs Form -->
        <div class="col-12 col-xl-8">
            <div class="card shadow-sm border-0">
                <div class="card-header p-0 bg-light border-bottom">
                    <ul class="nav nav-tabs px-4 pt-3 border-0" id="settingsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold text-secondary px-3 pb-3 border-0" id="school-tab" data-bs-toggle="tab" data-bs-target="#school-pane" type="button" role="tab" aria-controls="school-pane" aria-selected="true">
                                <i class="bi bi-mortarboard-fill me-1"></i> School Profile
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold text-secondary px-3 pb-3 border-0" id="apis-tab" data-bs-toggle="tab" data-bs-target="#apis-pane" type="button" role="tab" aria-controls="apis-pane" aria-selected="false">
                                <i class="bi bi-key-fill me-1"></i> API Integrations
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold text-secondary px-3 pb-3 border-0" id="admission-tab" data-bs-toggle="tab" data-bs-target="#admission-pane" type="button" role="tab" aria-controls="admission-pane" aria-selected="false">
                                <i class="bi bi-file-earmark-text-fill me-1"></i> Admission Letters
                            </button>
                        </li>
                    </ul>
                </div>
                
                <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body p-4">
                        <div class="tab-content" id="settingsTabContent">
                            <!-- School Branding -->
                            <div class="tab-pane fade show active" id="school-pane" role="tabpanel" aria-labelledby="school-tab" tabindex="0">
                                <h5 class="fw-bold mb-4 text-secondary">School Branding & Contact</h5>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">School Name</label>
                                        <input type="text" class="form-control" name="school_name" value="{{ $settings['school_name'] ?? '' }}" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">Contact Email</label>
                                        <input type="email" class="form-control" name="school_email" value="{{ $settings['school_email'] ?? '' }}" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">Contact Phone</label>
                                        <input type="text" class="form-control" name="school_phone" value="{{ $settings['school_phone'] ?? '' }}" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">School Logo</label>
                                        <input type="file" class="form-control" name="school_logo" accept="image/*">
                                        <small class="text-muted">Recommended: Square format, PNG/JPG, max 2MB.</small>
                                        @if(!empty($settings['school_logo']))
                                            <div class="mt-2 d-flex align-items-center gap-3 p-2 border rounded bg-light">
                                                <img src="{{ asset($settings['school_logo']) }}" alt="School Logo" style="height: 50px; object-fit: contain;">
                                                <div class="form-check">
                                                    <input class="form-check-input text-danger" type="checkbox" name="delete_school_logo" id="deleteLogo">
                                                    <label class="form-check-label text-danger fw-semibold" for="deleteLogo" style="font-size: 0.85rem;">
                                                        Remove Logo
                                                    </label>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">School Favicon</label>
                                        <input type="file" class="form-control" name="school_favicon" accept=".ico,.png,.jpg,.jpeg">
                                        <small class="text-muted">Recommended: .ico format or 32x32 PNG, max 512KB.</small>
                                        @if(!empty($settings['school_favicon']))
                                            <div class="mt-2 d-flex align-items-center gap-3 p-2 border rounded bg-light">
                                                <img src="{{ asset($settings['school_favicon']) }}" alt="School Favicon" style="height: 30px; width: 30px; object-fit: contain;">
                                                <div class="form-check">
                                                    <input class="form-check-input text-danger" type="checkbox" name="delete_school_favicon" id="deleteFavicon">
                                                    <label class="form-check-label text-danger fw-semibold" for="deleteFavicon" style="font-size: 0.85rem;">
                                                        Remove Favicon
                                                    </label>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">School Address</label>
                                        <textarea class="form-control" name="school_address" rows="3" required>{{ $settings['school_address'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- API Integrations -->
                            <div class="tab-pane fade" id="apis-pane" role="tabpanel" aria-labelledby="apis-tab" tabindex="0">
                                <h5 class="fw-bold mb-4 text-secondary">SMS alerts (Termii) & OCR configurations (OpenRouter)</h5>
                                <div class="row g-4">
                                    <!-- Termii Settings -->
                                    <div class="col-12 border-bottom pb-4 mb-2">
                                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-chat-text-fill text-primary me-2"></i>Termii SMS API</h6>
                                        <div class="row g-3">
                                            <div class="col-12 col-md-8">
                                                <label class="form-label fw-semibold">Termii API Key</label>
                                                <input type="password" class="form-control" name="termii_api_key" value="{{ $settings['termii_api_key'] ?? '' }}" placeholder="Paste your API key here">
                                                <small class="text-muted">Leave empty to use Mock SMS Mode for offline local testing.</small>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label class="form-label fw-semibold">Sender ID</label>
                                                <input type="text" class="form-control" name="termii_sender_id" value="{{ $settings['termii_sender_id'] ?? '' }}" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- OpenRouter Settings -->
                                    <div class="col-12">
                                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-cpu-fill text-warning me-2"></i>OpenRouter OCR Engine</h6>
                                        <div class="row g-3">
                                            <div class="col-12 col-md-8">
                                                <label class="form-label fw-semibold">OpenRouter API Key</label>
                                                <input type="password" class="form-control" name="openrouter_api_key" value="{{ $settings['openrouter_api_key'] ?? '' }}" placeholder="sk-or-v1-...">
                                                <small class="text-muted">Leave empty to use Mock OCR Mode for offline parsing.</small>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label class="form-label fw-semibold">OCR Model</label>
                                                <input type="text" class="form-control" name="openrouter_model" value="{{ $settings['openrouter_model'] ?? '' }}" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Admission Letter Config -->
                            <div class="tab-pane fade" id="admission-pane" role="tabpanel" aria-labelledby="admission-tab" tabindex="0">
                                <h5 class="fw-bold mb-4 text-secondary">Admission Registration & Cutoff Settings</h5>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">Registration Prefix</label>
                                        <input type="text" class="form-control" name="admission_prefix" value="{{ $settings['admission_prefix'] ?? 'SAC' }}" required>
                                        <small class="text-muted">Example: SAC -> SAC-0001</small>
                                    </div>

                                    <!-- Junior Cutoff -->
                                    <div class="col-12 col-md-3">
                                        <label class="form-label fw-semibold">Junior Cutoff Mark (%)</label>
                                        <input type="number" min="0" max="100" class="form-control" name="admission_junior_cutoff" value="{{ $settings['admission_junior_cutoff'] ?? '50' }}" required>
                                        <small class="text-muted">JSS1 - JSS3 cutoff mark</small>
                                    </div>
                                    
                                    <!-- Senior Cutoff -->
                                    <div class="col-12 col-md-3">
                                        <label class="form-label fw-semibold">Senior Cutoff Mark (%)</label>
                                        <input type="number" min="0" max="100" class="form-control" name="admission_senior_cutoff" value="{{ $settings['admission_senior_cutoff'] ?? '50' }}" required>
                                        <small class="text-muted">SS1 - SS3 cutoff mark</small>
                                    </div>

                                    <!-- Oral Interview Date -->
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Oral Interview Invitation Date / Instructions</label>
                                        <input type="text" class="form-control" name="admission_interview_date" value="{{ $settings['admission_interview_date'] ?? 'Saturday, July 18, 2026' }}" required placeholder="e.g. Saturday, July 18, 2026 at 9:00 AM">
                                        <small class="text-muted">This message will be shown to automatically admitted candidates on their result check screen.</small>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Admission Letter Template Text</label>
                                        <textarea class="form-control" name="admission_letter_template" rows="8" required>{{ $settings['admission_letter_template'] ?? '' }}</textarea>
                                        <small class="text-muted">Supported tags: <code>{firstname}</code>, <code>{surname}</code>, <code>{registration_number}</code>, <code>{class}</code>, <code>{session}</code></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light px-4 py-3 d-flex justify-content-end border-top">
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="bi bi-save-fill"></i> Save Settings Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Academic Sessions Management -->
        <div class="col-12 col-xl-4">
            <!-- Add Session Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header">
                    <h5 class="m-0 fw-bold text-dark">Add Academic Session</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.session.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Session Name</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. 2026/2027" required>
                            <small class="text-muted">Must be in the format: YYYY/YYYY</small>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            Register Session
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sessions List Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header">
                    <h5 class="m-0 fw-bold text-dark">Academic Sessions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush rounded-bottom-4">
                        @foreach($sessions as $session)
                            <div class="list-group-item d-flex align-items-center justify-content-between p-3">
                                <div>
                                    <span class="fw-semibold text-dark">{{ $session->name }}</span>
                                    @if($session->is_current)
                                        <span class="badge bg-success ms-2" style="font-size: 0.7rem;">Active Session</span>
                                    @endif
                                </div>
                                <div>
                                    @if(!$session->is_current)
                                        <form action="{{ route('settings.session.current', $session->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-primary btn-sm py-1">
                                                Set Current
                                            </button>
                                        </form>
                                    @else
                                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Add Class Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header">
                    <h5 class="m-0 fw-bold text-dark">Add School Class</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.class.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Class Name</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. JSS3" required>
                            <small class="text-muted">Example: JSS3, SS2, etc.</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            Register Class
                        </button>
                    </form>
                </div>
            </div>

            <!-- Classes List Card -->
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <h5 class="m-0 fw-bold text-dark">School Classes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush rounded-bottom-4">
                        @foreach($classes as $class)
                            <div class="list-group-item d-flex align-items-center justify-content-between p-3">
                                <div>
                                    <span class="fw-semibold text-dark">{{ $class->name }}</span>
                                </div>
                                <div>
                                    @php
                                        $applicantCountForClass = \App\Models\Applicant::where('class_applying_for', $class->name)->count();
                                    @endphp
                                    @if($applicantCountForClass === 0)
                                        <form action="{{ route('settings.class.destroy', $class->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this class?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.72rem;">{{ $applicantCountForClass }} Candidates</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
