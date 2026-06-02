@extends('layouts.app')

@section('title', 'Edit Applicant')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Edit Applicant Details</h3>
            <p class="text-muted m-0">Modify information for {{ $applicant->full_name }} ({{ $applicant->registration_number }}).</p>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <form action="{{ route('applicants.update', $applicant->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="card-body p-4">
                <!-- Section: Personal Information -->
                <h5 class="fw-bold text-primary mb-3 border-bottom pb-2">Personal Information</h5>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Surname <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('surname') is-invalid @enderror" name="surname" value="{{ old('surname', $applicant->surname) }}" required>
                        @error('surname') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name', $applicant->first_name) }}" required>
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Other Name</label>
                        <input type="text" class="form-control" name="other_name" value="{{ old('other_name', $applicant->other_name) }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                        <select class="form-select @error('gender') is-invalid @enderror" name="gender" required>
                            <option value="Male" {{ old('gender', $applicant->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender', $applicant->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" name="date_of_birth" value="{{ old('date_of_birth', $applicant->date_of_birth->format('Y-m-d')) }}" required>
                        @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Nationality <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nationality') is-invalid @enderror" name="nationality" value="{{ old('nationality', $applicant->nationality) }}" required>
                        @error('nationality') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">State of Origin <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('state_of_origin') is-invalid @enderror" name="state_of_origin" value="{{ old('state_of_origin', $applicant->state_of_origin) }}" required>
                        @error('state_of_origin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">LGA of Origin <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('lga') is-invalid @enderror" name="lga" value="{{ old('lga', $applicant->lga) }}" required>
                        @error('lga') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <!-- Section: Contact Information -->
                <h5 class="fw-bold text-primary mb-3 border-bottom pb-2">Contact Information</h5>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Parent Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('parent_phone_number') is-invalid @enderror" name="parent_phone_number" value="{{ old('parent_phone_number', $applicant->parent_phone_number) }}" required>
                        @error('parent_phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email', $applicant->email) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Home Address <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3" required>{{ old('address', $applicant->address) }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <!-- Section: Academic & Uploads -->
                <h5 class="fw-bold text-primary mb-3 border-bottom pb-2">Academic & Document Uploads</h5>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Class Applying For <span class="text-danger">*</span></label>
                        <select class="form-select @error('class_applying_for') is-invalid @enderror" name="class_applying_for" required>
                            @foreach(\App\Models\SchoolClass::orderBy('name', 'asc')->get() as $class)
                                <option value="{{ $class->name }}" {{ old('class_applying_for', $applicant->class_applying_for) === $class->name ? 'selected' : '' }}>{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('class_applying_for') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Exam Batch <span class="text-danger">*</span></label>
                        <select class="form-select @error('exam_batch') is-invalid @enderror" name="exam_batch" required>
                            <option value="Batch A" {{ old('exam_batch', $applicant->exam_batch) === 'Batch A' ? 'selected' : '' }}>Batch A</option>
                            <option value="Batch B" {{ old('exam_batch', $applicant->exam_batch) === 'Batch B' ? 'selected' : '' }}>Batch B</option>
                            <option value="Batch C" {{ old('exam_batch', $applicant->exam_batch) === 'Batch C' ? 'selected' : '' }}>Batch C</option>
                            <option value="Resit" {{ old('exam_batch', $applicant->exam_batch) === 'Resit' ? 'selected' : '' }}>Resit</option>
                        </select>
                        @error('exam_batch') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Passport Photograph</label>
                        <input type="file" class="form-control" name="passport" accept="image/*">
                        <small class="text-muted d-block">Leave blank to keep existing. Accepts JPEG, PNG. Max: 2MB.</small>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Birth Certificate</label>
                        <input type="file" class="form-control" name="birth_certificate" accept=".pdf,image/*">
                        <small class="text-muted d-block">Leave blank to keep existing. Accepts PDF, JPEG, PNG. Max: 5MB.</small>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Previous School Result</label>
                        <input type="file" class="form-control" name="school_result" accept=".pdf,image/*">
                        <small class="text-muted d-block">Leave blank to keep existing. Accepts PDF, JPEG, PNG. Max: 5MB.</small>
                    </div>
                </div>
            </div>
            
            <div class="card-footer bg-light px-4 py-3 d-flex justify-content-between border-top">
                <a href="{{ route('applicants.show', $applicant->id) }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="bi bi-save-fill"></i> Save Updates
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
