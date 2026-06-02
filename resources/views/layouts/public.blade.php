<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admission Result Checker') - {{ \App\Models\Setting::get('school_name', "St. Augustine's College") }}</title>
    
    <!-- Google Fonts (Outfit) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0B5ED7;
            --secondary-color: #198754;
            --accent-color: #FFC107;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
            --text-main: #334155;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar-public {
            background-color: #0f172a;
            border-bottom: 3px solid var(--accent-color);
            padding: 1rem 0;
        }

        .navbar-brand-text {
            color: #ffffff;
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
        }

        .navbar-brand-text span {
            color: var(--accent-color);
        }

        .main-content {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .footer-public {
            background-color: #0f172a;
            color: #94a3b8;
            padding: 1.5rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.88rem;
        }

        /* Glassmorphic/Premium Cards */
        .glass-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #0a58ca;
            border-color: #0a53be;
            transform: translateY(-1px);
        }

        .badge-status {
            padding: 0.5rem 1rem;
            font-weight: 600;
            border-radius: 50rem;
            font-size: 0.85rem;
            display: inline-block;
        }

        .badge-admitted { background-color: #dcfce7; color: #166534; }
        .badge-failed { background-color: #fee2e2; color: #991b1b; }
        .badge-passed { background-color: #d1fae5; color: #065f46; }
        .badge-pending { background-color: #f1f5f9; color: #475569; }
        .badge-review { background-color: #dbeafe; color: #1e40af; }
        .badge-exam-sch { background-color: #fef3c7; color: #92400e; }
        .badge-exam-writ { background-color: #fae8ff; color: #86198f; }
        .badge-rejected { background-color: #ffe4e6; color: #9f1239; }

        @media (max-width: 576px) {
            .main-content {
                padding: 1.5rem 0.5rem;
            }
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Public Header/Navbar -->
    <nav class="navbar navbar-dark navbar-public shadow-sm">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="navbar-brand d-flex align-items-center gap-2">
                <i class="bi bi-mortarboard-fill text-warning fs-3"></i>
                <div class="navbar-brand-text">
                    {{ \App\Models\Setting::get('school_name', "St. Augustine's College") }}
                </div>
            </div>
            <div>
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm fw-semibold d-flex align-items-center gap-1 px-3">
                    <i class="bi bi-shield-lock-fill"></i> Staff Portal
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="container">
            @yield('content')
        </div>
    </div>

    <!-- Public Footer -->
    <footer class="footer-public text-center">
        <div class="container">
            <p class="mb-1">&copy; {{ date('Y') }} {{ \App\Models\Setting::get('school_name', "St. Augustine's College, Ibusa") }}. All Rights Reserved.</p>
            <small class="text-muted">For admissions assistance, please contact the administrator: {{ \App\Models\Setting::get('school_phone', '+234 803 000 0000') }} | {{ \App\Models\Setting::get('school_email', 'admissions@staugustineibusa.com') }}</small>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
