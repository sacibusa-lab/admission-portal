@extends('layouts.app')

@section('title', 'Admission Letter Preview')

@section('content')
<div class="container-fluid p-0">
    <!-- Back Navigation -->
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('applicants.show', $applicant->id) }}" class="btn btn-outline-secondary btn-sm rounded-circle p-2" style="width: 32px; height: 32px; line-height: 12px;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span class="text-muted">Back to Applicant Profile</span>
    </div>

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark m-0">Admission Letter</h3>
            <p class="text-muted m-0">Preview the generated admission letter for {{ $applicant->full_name }}.</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="printLetter()" class="btn btn-outline-primary d-flex align-items-center gap-2">
                <i class="bi bi-printer-fill"></i> Print Letter
            </button>
            <a href="{{ route('letters.pdf', $applicant->id) }}" class="btn btn-success fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
            </a>
        </div>
    </div>

    <!-- Letter Preview Area -->
    <div class="card shadow-sm border-0 mx-auto" style="max-width: 800px;">
        <div class="card-body p-5" id="printableLetterArea" style="background-color: #ffffff; color: #1e293b;">
            <!-- School Letterhead Header -->
            <div class="text-center border-bottom pb-4 mb-4">
                <h3 class="fw-extrabold text-primary mb-1 text-uppercase" style="letter-spacing: 0.5px;">{{ $schoolName }}</h3>
                <p class="text-muted m-0" style="font-size: 0.9rem;">{{ $schoolAddress }}</p>
                <p class="text-muted m-0" style="font-size: 0.85rem;">Email: {{ $schoolEmail }} | Phone: {{ $schoolPhone }}</p>
            </div>

            <!-- Date and Recipient -->
            <div class="row mb-4" style="font-size: 0.95rem;">
                <div class="col-8">
                    <p class="m-0 text-muted">Date: <strong>{{ now()->format('d M, Y') }}</strong></p>
                    <p class="mt-3 mb-0">To:</p>
                    <h5 class="fw-bold text-dark m-0">{{ $applicant->full_name }}</h5>
                    <p class="m-0 text-muted">{{ $applicant->address }}</p>
                </div>
                <div class="col-4 text-end">
                    <span class="text-muted d-block" style="font-size: 0.85rem;">Registration Number:</span>
                    <strong class="text-dark fs-5">{{ $applicant->registration_number }}</strong>
                </div>
            </div>

            <!-- Title -->
            <div class="text-center my-4">
                <h5 class="fw-bold text-dark text-uppercase border-bottom border-dark d-inline-block pb-1" style="letter-spacing: 0.5px;">LETTER OF PROVISIONAL ADMISSION</h5>
            </div>

            <!-- Body -->
            <div style="font-size: 1rem; line-height: 1.6; white-space: pre-line;" class="text-dark">
                {!! nl2br(e($letterContent)) !!}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function printLetter() {
        const printableContent = document.getElementById('printableLetterArea').innerHTML;
        const originalContent = document.body.innerHTML;

        // Open a new print window to ensure correct styling and headers
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Admission Letter - ${ {!! json_encode($applicant->registration_number) !!} }</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { font-family: 'Outfit', sans-serif; padding: 3rem; background-color: #fff; color: #1e293b; }
                        .text-primary { color: #0b5ed7 !important; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        ${printableContent}
                    </div>
                    <script>
                        window.onload = function() {
                            window.print();
                            window.close();
                        };
                    <\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }
</script>
@endsection
