<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Slip - {{ $applicant->registration_number }}</title>
    
    <!-- Google Fonts (Outfit) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #ffffff;
            color: #1e293b;
            padding: 2rem;
        }

        .slip-container {
            border: 2px solid #0f172a;
            border-radius: 12px;
            padding: 2.5rem;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }

        .slip-header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .school-info h4 {
            font-weight: 800;
            margin: 0;
            color: #0b5ed7;
            text-transform: uppercase;
        }

        .school-info p {
            margin: 2px 0 0 0;
            font-size: 0.88rem;
            color: #64748b;
        }

        .slip-title {
            text-transform: uppercase;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            border: 1px solid #1e293b;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            background-color: #f8fafc;
        }

        .info-grid td {
            padding: 0.6rem 0.5rem;
        }

        .photo-box {
            width: 130px;
            height: 130px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8fafc;
        }

        .signature-area {
            margin-top: 3.5rem;
            border-top: 1px dashed #cbd5e1;
            padding-top: 1rem;
        }

        /* Printable optimization */
        @media print {
            body {
                padding: 0;
            }
            .slip-container {
                border: none;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- Print Control Bar -->
    <div class="max-width-800 mx-auto text-end mb-4 no-print" style="max-width: 800px;">
        <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
            <i class="bi bi-printer-fill"></i> Print Registration Slip
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm ms-2">
            Close Tab
        </button>
    </div>

    <!-- Slip container -->
    <div class="slip-container">
        <!-- Header -->
        <div class="slip-header">
            <div class="school-info">
                <h4>{{ \App\Models\Setting::get('school_name', "St. Augustine's College, Ibusa") }}</h4>
                <p>{{ \App\Models\Setting::get('school_address', 'Ibusa, Delta State, Nigeria') }}</p>
                <p>Email: {{ \App\Models\Setting::get('school_email', 'info@staugustineibusa.com') }} | Tel: {{ \App\Models\Setting::get('school_phone', '+2348030000000') }}</p>
            </div>
            <div class="slip-title">
                Registration Slip
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Info Block -->
            <div class="col-8">
                <table class="w-100 info-grid" style="font-size: 0.95rem;">
                    <tr>
                        <td style="width: 180px;" class="text-muted">Registration Number:</td>
                        <td><strong class="text-dark fs-5">{{ $applicant->registration_number }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Class Applied For:</td>
                        <td><strong class="text-dark">{{ $applicant->class_applying_for }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Academic Session:</td>
                        <td>{{ $applicant->academicSession->name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Surname:</td>
                        <td><strong class="text-dark">{{ $applicant->surname }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">First Name:</td>
                        <td><strong class="text-dark">{{ $applicant->first_name }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Other Name:</td>
                        <td>{{ $applicant->other_name ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Gender / Date of Birth:</td>
                        <td>{{ $applicant->gender }} / {{ $applicant->date_of_birth->format('d M, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">State / LGA of Origin:</td>
                        <td>{{ $applicant->state_of_origin }} / {{ $applicant->lga }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Parent Phone:</td>
                        <td>{{ $applicant->parent_phone_number }}</td>
                    </tr>
                </table>
            </div>

            <!-- Right Photo Block -->
            <div class="col-4 d-flex justify-content-end align-items-start">
                <div class="photo-box shadow-sm">
                    @if($applicant->passport_path)
                        <img src="{{ asset('storage/' . $applicant->passport_path) }}" alt="Passport" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <span class="text-muted" style="font-size: 0.8rem;">Passport photo</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Footnote details -->
        <div class="row signature-area" style="font-size: 0.88rem;">
            <div class="col-6">
                <div class="text-start">
                    <div style="height: 50px;"></div>
                    <div class="border-top border-dark d-inline-block pt-1" style="width: 200px;">Applicant Signature / Date</div>
                </div>
            </div>
            <div class="col-6">
                <div class="text-end">
                    <div style="height: 50px;"></div>
                    <div class="border-top border-dark d-inline-block pt-1 text-center" style="width: 200px;">Admission Officer Sign</div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 pt-3 border-top text-muted" style="font-size: 0.75rem;">
            Printed on {{ now()->format('d M, Y H:i:s') }} &bull; St. Augustine's College Admission Management Portal.
        </div>
    </div>

    <!-- Auto Print Script -->
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            // Auto open print dialog
            window.print();
        });
    </script>
</body>
</html>
