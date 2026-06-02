@extends('layouts.app')

@section('title', 'Register Applicant')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Register Applicant</h3>
            <p class="text-muted m-0">Create a new applicant record manually or use AI OCR to speed up data entry.</p>
        </div>
    </div>

    <!-- OCR Scanning Card -->
    <div class="card shadow-sm border-0 mb-4 bg-light border-start border-4 border-warning">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-12 col-lg-7">
                    <h5 class="fw-bold text-dark mb-2"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> AI Fast Registration (OCR Extraction)</h5>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Upload a Birth Certificate or Previous School Result. Our AI will analyze the document, extract name, date of birth, gender, school, and scores, and let you auto-fill the form instantly.</p>
                </div>
                <div class="col-12 col-lg-5 mt-3 mt-lg-0 text-lg-end">
                    <div class="d-flex justify-content-lg-end align-items-center gap-2">
                        <input type="file" id="ocr_file_input" accept=".pdf,.jpeg,.png,.jpg" class="d-none">
                        <button type="button" class="btn btn-outline-warning fw-semibold d-flex align-items-center gap-2" onclick="document.getElementById('ocr_file_input').click()">
                            <i class="bi bi-file-earmark-medical-fill"></i> Select Scan File
                        </button>
                        <button type="button" class="btn btn-warning fw-semibold d-flex align-items-center gap-2" id="btn_scan_ocr" disabled>
                            <i class="bi bi-qr-code-scan"></i> Scan Document
                        </button>
                    </div>
                    <span class="d-block mt-2 text-muted text-start text-lg-end" id="ocr_file_selected" style="font-size: 0.8rem;">No file chosen</span>
                </div>
            </div>

            <!-- OCR Progress Bar / Result Display -->
            <div class="mt-3 d-none" id="ocr_progress_area">
                <div class="d-flex align-items-center gap-2 mb-2 text-primary">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <span style="font-size: 0.9rem; font-weight: 500;">Reading document and extracting data via OpenRouter...</span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width: 100%"></div>
                </div>
            </div>

            <!-- OCR Result Preview -->
            <div class="mt-3 p-3 bg-white border rounded-3 d-none" id="ocr_result_area">
                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-clipboard2-check-fill text-success"></i> Extracted Data Preview
                </h6>
                <div class="row g-3" style="font-size: 0.88rem;">
                    <div class="col-6 col-md-3">
                        <span class="text-muted d-block">Surname:</span>
                        <strong id="ocr_surname" class="text-dark">-</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted d-block">Firstname:</span>
                        <strong id="ocr_first_name" class="text-dark">-</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted d-block">Gender:</span>
                        <strong id="ocr_gender" class="text-dark">-</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted d-block">DOB:</span>
                        <strong id="ocr_date_of_birth" class="text-dark">-</strong>
                    </div>
                    <div class="col-12 col-md-6">
                        <span class="text-muted d-block">Previous School:</span>
                        <strong id="ocr_previous_school" class="text-dark">-</strong>
                    </div>
                    <div class="col-12 col-md-6">
                        <span class="text-muted d-block">Exam Scores:</span>
                        <strong id="ocr_exam_scores" class="text-dark">-</strong>
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <button type="button" class="btn btn-success btn-sm fw-semibold d-flex align-items-center gap-2 ms-auto" id="btn_autofill">
                        <i class="bi bi-magic"></i> Auto-fill Registration Form
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Registration Form -->
    <div class="card shadow-sm border-0">
        <form action="{{ route('applicants.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Hidden input for OCR metadata logs -->
            <input type="hidden" name="ocr_metadata" id="ocr_metadata_input">

            <div class="card-body p-4">
                <!-- Section: Personal Information -->
                <h5 class="fw-bold text-primary mb-3 border-bottom pb-2">Personal Information</h5>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Surname <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('surname') is-invalid @enderror" name="surname" id="form_surname" value="{{ old('surname') }}" required>
                        @error('surname') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" id="form_first_name" value="{{ old('first_name') }}" required>
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Other Name</label>
                        <input type="text" class="form-control" name="other_name" id="form_other_name" value="{{ old('other_name') }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                        <select class="form-select @error('gender') is-invalid @enderror" name="gender" id="form_gender" required>
                            <option value="" disabled selected>Select gender...</option>
                            <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" name="date_of_birth" id="form_date_of_birth" value="{{ old('date_of_birth') }}" required>
                        @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Nationality <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nationality') is-invalid @enderror" name="nationality" value="{{ old('nationality', 'Nigerian') }}" required>
                        @error('nationality') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">State of Origin <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('state_of_origin') is-invalid @enderror" name="state_of_origin" value="{{ old('state_of_origin') }}" required placeholder="e.g. Delta">
                        @error('state_of_origin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">LGA of Origin <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('lga') is-invalid @enderror" name="lga" value="{{ old('lga') }}" required placeholder="e.g. Oshimili North">
                        @error('lga') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <!-- Section: Contact Information -->
                <h5 class="fw-bold text-primary mb-3 border-bottom pb-2">Contact Information</h5>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Parent Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('parent_phone_number') is-invalid @enderror" name="parent_phone_number" value="{{ old('parent_phone_number') }}" required placeholder="e.g. 080xxxxxxxx">
                        @error('parent_phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="e.g. parent@example.com">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Home Address <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3" required>{{ old('address') }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <!-- Section: Academic & Uploads -->
                <h5 class="fw-bold text-primary mb-3 border-bottom pb-2">Academic & Document Uploads</h5>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Class Applying For <span class="text-danger">*</span></label>
                        <select class="form-select @error('class_applying_for') is-invalid @enderror" name="class_applying_for" required>
                            <option value="" disabled selected>Select target class...</option>
                            @foreach(\App\Models\SchoolClass::orderBy('name', 'asc')->get() as $class)
                                <option value="{{ $class->name }}" {{ old('class_applying_for') === $class->name ? 'selected' : '' }}>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('class_applying_for') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Exam Batch <span class="text-danger">*</span></label>
                        <select class="form-select @error('exam_batch') is-invalid @enderror" name="exam_batch" required>
                            <option value="Batch A" {{ old('exam_batch') === 'Batch A' ? 'selected' : '' }}>Batch A</option>
                            <option value="Batch B" {{ old('exam_batch') === 'Batch B' ? 'selected' : '' }}>Batch B</option>
                            <option value="Batch C" {{ old('exam_batch') === 'Batch C' ? 'selected' : '' }}>Batch C</option>
                            <option value="Resit" {{ old('exam_batch') === 'Resit' ? 'selected' : '' }}>Resit</option>
                        </select>
                        @error('exam_batch') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Passport Photograph <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('passport') is-invalid @enderror" name="passport" accept="image/*" required>
                        <small class="text-muted">Must be an image (JPEG, PNG). Max size: 2MB.</small>
                        @error('passport') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Birth Certificate <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('birth_certificate') is-invalid @enderror" name="birth_certificate" accept=".pdf,image/*" required>
                        <small class="text-muted">Accepts PDF or images. Max size: 5MB.</small>
                        @error('birth_certificate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Previous School Result <span class="text-danger">*</span></label>
                        <input type="file" class="form-control @error('school_result') is-invalid @enderror" name="school_result" accept=".pdf,image/*" required>
                        <small class="text-muted">Accepts PDF or images. Max size: 5MB.</small>
                        @error('school_result') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            
            <div class="card-footer bg-light px-4 py-3 d-flex justify-content-between border-top">
                <a href="{{ route('applicants.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="bi bi-person-check-fill"></i> Register Applicant
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let extractedData = null;
    let selectedOcrFile = null;

    // File Selector event
    document.getElementById('ocr_file_input').addEventListener('change', function(e) {
        if(e.target.files.length > 0) {
            selectedOcrFile = e.target.files[0];
            document.getElementById('ocr_file_selected').innerText = selectedOcrFile.name;
            document.getElementById('btn_scan_ocr').disabled = false;
        } else {
            selectedOcrFile = null;
            document.getElementById('ocr_file_selected').innerText = 'No file chosen';
            document.getElementById('btn_scan_ocr').disabled = true;
        }
    });

    // Scan event trigger
    document.getElementById('btn_scan_ocr').addEventListener('click', function() {
        if(!selectedOcrFile) return;

        const progressArea = document.getElementById('ocr_progress_area');
        const resultArea = document.getElementById('ocr_result_area');
        
        progressArea.classList.remove('d-none');
        resultArea.classList.add('d-none');

        const formData = new FormData();
        formData.append('document', selectedOcrFile);

        fetch("{{ route('ocr.process') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        })
        .then(response => {
            if(!response.ok) {
                throw new Error('Server returned an error.');
            }
            return response.json();
        })
        .then(res => {
            progressArea.classList.add('d-none');
            
            if(res.success && res.data) {
                extractedData = res.data;
                document.getElementById('ocr_metadata_input').value = JSON.stringify(res.data);
                
                // Show values in preview
                document.getElementById('ocr_surname').innerText = res.data.surname || 'Not found';
                document.getElementById('ocr_first_name').innerText = res.data.first_name || 'Not found';
                document.getElementById('ocr_gender').innerText = res.data.gender || 'Not found';
                document.getElementById('ocr_date_of_birth').innerText = res.data.date_of_birth || 'Not found';
                document.getElementById('ocr_previous_school').innerText = res.data.previous_school || 'Not found';
                document.getElementById('ocr_exam_scores').innerText = res.data.exam_scores || 'Not found';

                resultArea.classList.remove('d-none');
            } else {
                alert(res.error || 'Failed to parse document text.');
            }
        })
        .catch(err => {
            progressArea.classList.add('d-none');
            alert(err.message || 'An error occurred during scanning.');
        });
    });

    // Autofill form inputs
    document.getElementById('btn_autofill').addEventListener('click', function() {
        if(!extractedData) return;

        if(extractedData.surname) document.getElementById('form_surname').value = extractedData.surname;
        if(extractedData.first_name) document.getElementById('form_first_name').value = extractedData.first_name;
        if(extractedData.other_name) document.getElementById('form_other_name').value = extractedData.other_name;
        if(extractedData.date_of_birth) document.getElementById('form_date_of_birth').value = extractedData.date_of_birth;
        
        if(extractedData.gender) {
            const genderSelect = document.getElementById('form_gender');
            if(extractedData.gender.toLowerCase().includes('male')) {
                genderSelect.value = 'Male';
            } else if(extractedData.gender.toLowerCase().includes('female')) {
                genderSelect.value = 'Female';
            }
        }

        alert('Registration form fields successfully populated!');
    });
</script>
@endsection
