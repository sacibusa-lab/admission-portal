@extends('layouts.public')

@section('title', 'Check Entrance Exam Result')

@section('content')
<div class="row justify-content-center animate__animated animate__fadeIn" style="margin-top: 2rem; margin-bottom: 2rem;">
    <div class="col-12 col-md-8 col-lg-5">
        <div class="glass-card p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 72px; height: 72px;">
                    <i class="bi bi-person-badge fs-1"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">Check Your Result</h3>
                <p class="text-muted">Enter your Candidate Registration Number below to check your entrance exam result.</p>
            </div>

            <!-- Error Alerts -->
            @if(session('error'))
                <div class="alert alert-danger border-0 shadow-sm mb-4 d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div>
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            <form action="{{ route('public.results.check') }}" method="POST">
                @csrf

                <!-- Registration Number -->
                <div class="mb-4">
                    <label for="registration_number" class="form-label fw-semibold text-secondary">Candidate Registration Number</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-hash"></i></span>
                        <input type="text" 
                               name="registration_number" 
                               id="registration_number" 
                               class="form-control border-start-0 ps-0 @error('registration_number') is-invalid @enderror" 
                               placeholder="e.g. SAC-000123" 
                               value="{{ old('registration_number') }}" 
                               required 
                               style="height: 48px;">
                    </div>
                    @error('registration_number')
                        <div class="text-danger mt-1 small" style="font-size: 0.8rem;">
                            {{ $message }}
                        </div>
                    @enderror
                    <div class="form-text text-muted" style="font-size: 0.78rem;">
                        This is the registration number assigned to you during application (e.g., SAC-XXXX).
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary w-100 fw-bold d-flex align-items-center justify-content-center gap-2" style="height: 48px;">
                        <i class="bi bi-search"></i> Fetch Result & Status
                    </button>
                </div>
            </form>

            <hr class="my-4 text-muted opacity-25">

            <div class="bg-light p-3 rounded-3" style="font-size: 0.82rem;">
                <h6 class="fw-bold text-dark mb-1"><i class="bi bi-info-circle-fill text-primary me-1"></i> Need Help?</h6>
                <p class="text-muted mb-0">If you have misplaced your registration number or cannot fetch your results, please contact the admissions department for assistance.</p>
            </div>
        </div>
    </div>
</div>
@endsection
