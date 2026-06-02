@extends('layouts.app')

@section('title', 'CSV Batch Import')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">CSV Batch Import</h3>
            <p class="text-muted m-0">Upload a CSV file to register multiple applicants at once.</p>
        </div>
    </div>

    <!-- Alert Summary -->
    @if(session('import_summary'))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="fw-bold text-dark m-0"><i class="bi bi-info-circle-fill text-primary me-2"></i>Import Operations Summary</h6>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3">
                            <span class="text-muted d-block" style="font-size: 0.8rem; font-weight: 500;">TOTAL EVALUATED</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0">{{ session('import_summary')['total'] }}</h3>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 border-success-subtle bg-success bg-opacity-10 text-success">
                            <span class="d-block" style="font-size: 0.8rem; font-weight: 500;">SUCCESSFUL</span>
                            <h3 class="fw-bold mt-1 mb-0">{{ session('import_summary')['successful'] }}</h3>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 border-danger-subtle bg-danger bg-opacity-10 text-danger">
                            <span class="d-block" style="font-size: 0.8rem; font-weight: 500;">FAILED</span>
                            <h3 class="fw-bold mt-1 mb-0">{{ session('import_summary')['failed'] }}</h3>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 d-flex flex-column justify-content-center align-items-center h-100">
                            @if(session('import_summary')['has_errors'])
                                <a href="{{ route('applicants.import.errors') }}" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
                                    <i class="bi bi-file-earmark-arrow-down-fill"></i> Download Errors
                                </a>
                            @else
                                <span class="text-success" style="font-size: 0.85rem;"><i class="bi bi-check-circle-fill me-1"></i> Clean Import!</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Upload Form -->
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header">
                    <h5 class="m-0 fw-bold text-dark">Upload CSV File</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('applicants.import.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Select CSV Document</label>
                            <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                            <small class="text-muted d-block mt-2">Only standard <code>.csv</code> spreadsheet format is evaluated. Max file size: 2MB.</small>
                            <a href="{{ route('applicants.import.sample') }}" class="btn btn-outline-secondary btn-sm mt-3 fw-semibold d-inline-flex align-items-center gap-2">
                                <i class="bi bi-download"></i> Download Sample CSV Template
                            </a>
                        </div>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="bi bi-cloud-arrow-up-fill"></i> Import Applicants Batch
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- CSV Instructions -->
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header">
                    <h5 class="m-0 fw-bold text-dark">CSV File Guidelines</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted" style="font-size: 0.9rem;">Your CSV file must include exactly the headers shown below. Case-sensitive and spacing must match exactly.</p>
                    
                    <div class="border rounded p-3 bg-light mb-3">
                        <code style="font-size: 0.9rem;">Surname,Firstname,ParentPhone,Class</code>
                    </div>

                    <h6 class="fw-bold text-dark mb-2" style="font-size: 0.9rem;">Validation Parameters:</h6>
                    <ul class="text-muted" style="font-size: 0.88rem; padding-left: 1.2rem;">
                        <li class="mb-1"><strong>Surname & Firstname</strong>: Cannot be empty.</li>
                        <li class="mb-1"><strong>ParentPhone</strong>: Required parent contact details.</li>
                        @php
                            $classList = \App\Models\SchoolClass::orderBy('name', 'asc')->pluck('name')->toArray();
                            $classListStr = implode(', ', array_map(fn($c) => "<code>$c</code>", $classList));
                        @endphp
                        <li class="mb-1"><strong>Class</strong>: Must contain one of: {!! $classListStr !!}. Other names are rejected.</li>
                        <li class="mb-1"><strong>Duplicates</strong>: Combined first name, surname, and parent phone that exist in the active session will be skipped.</li>
                    </ul>

                    <h6 class="fw-bold text-dark mt-4 mb-2" style="font-size: 0.9rem;">Sample CSV Contents:</h6>
                    <pre class="bg-dark text-white rounded p-3" style="font-size: 0.78rem; line-height: 1.4;">Surname,Firstname,ParentPhone,Class
Alabi,John,08037654321,JSS1
Chidi,Jane,07057654321,SS1</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
