<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admission Portal') - {{ \App\Models\Setting::get('school_name', "St. Augustine's College") }}</title>
    
    @if(\App\Models\Setting::get('school_favicon'))
    <link rel="shortcut icon" href="{{ asset('storage/' . \App\Models\Setting::get('school_favicon')) }}" type="image/x-icon">
    <link rel="icon" href="{{ asset('storage/' . \App\Models\Setting::get('school_favicon')) }}" type="image/x-icon">
    @endif
    
    <!-- Google Fonts (Outfit) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom Layout Styles -->
    <style>
        :root {
            --primary-color: #0B5ED7;
            --secondary-color: #198754;
            --accent-color: #FFC107;
            --dark-sidebar: #0f172a;
            --dark-sidebar-hover: #1e293b;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
            --text-main: #334155;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* Sidebar Design */
        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--dark-sidebar);
            color: #ffffff;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand h5 {
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
            font-size: 1.1rem;
            color: #f8fafc;
        }

        .sidebar-menu {
            list-style: none;
            padding: 1.5rem 0.75rem;
            margin: 0;
            flex-grow: 1;
            overflow-y: auto;
        }

        .sidebar-item {
            margin-bottom: 0.35rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.8rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }

        .sidebar-link i {
            font-size: 1.15rem;
            transition: transform 0.2s ease;
        }

        .sidebar-link:hover {
            background-color: var(--dark-sidebar-hover);
            color: #ffffff;
        }

        .sidebar-link:hover i {
            transform: scale(1.1);
        }

        .sidebar-link.active {
            background-color: var(--primary-color);
            color: #ffffff;
        }

        /* Content Area Design */
        .wrapper {
            margin-left: 260px;
            min-height: 100vh;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            height: 70px;
            background-color: #ffffff;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .main-content {
            padding: 2rem;
            flex-grow: 1;
        }

        /* User Profile Area in Topbar */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: #e2e8f0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            border: 2px solid #cbd5e1;
        }

        /* Custom Cards */
        .card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Hover Effect for Cards */
        .card-hoverable:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.03);
        }

        /* Buttons Styling */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            background-color: #0a58ca;
            border-color: #0a53be;
            transform: translateY(-1px);
        }

        .btn-success {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .btn-success:hover {
            background-color: #146c43;
            border-color: #13653f;
            transform: translateY(-1px);
        }

        /* Tables Styling */
        .table-custom {
            background-color: #ffffff;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .table-custom th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .table-custom td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .table-custom tr:last-child td {
            border-bottom: none;
        }

        /* Badge status colors */
        .badge-pending { background-color: #e2e8f0; color: #475569; }
        .badge-review { background-color: #dbeafe; color: #1e40af; }
        .badge-exam-sch { background-color: #fef3c7; color: #92400e; }
        .badge-exam-writ { background-color: #fae8ff; color: #86198f; }
        .badge-passed { background-color: #d1fae5; color: #065f46; }
        .badge-failed { background-color: #fee2e2; color: #991b1b; }
        .badge-admitted { background-color: #dcfce7; color: #166534; font-weight: 600; }
        .badge-rejected { background-color: #ffe4e6; color: #9f1239; }

        /* Responsive Design Toggles */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-main);
        }

        @media (max-width: 991.98px) {
            .sidebar {
                left: -260px;
            }
            .sidebar.show {
                left: 0;
            }
            .wrapper {
                margin-left: 0;
            }
            .sidebar-toggle {
                display: block;
            }
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            @if(\App\Models\Setting::get('school_logo'))
                <img src="{{ asset('storage/' . \App\Models\Setting::get('school_logo')) }}" alt="Logo" style="height: 32px; width: 32px; object-fit: contain; border-radius: 4px;">
            @else
                <i class="bi bi-mortarboard-fill text-warning fs-3"></i>
            @endif
            <div>
                <h5>SAC Portal</h5>
                <small style="font-size: 0.7rem; color: #94a3b8;">Admission Management</small>
            </div>
        </div>
        
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ Route::is('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            @if(auth()->user()->hasPermission('register_applicants'))
            <li class="sidebar-item">
                <a href="{{ route('applicants.create') }}" class="sidebar-link {{ Route::is('applicants.create') ? 'active' : '' }}">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Register Applicant</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('applicants.import') }}" class="sidebar-link {{ Route::is('applicants.import') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-excel-fill"></i>
                    <span>CSV Batch Import</span>
                </a>
            </li>
            @endif

            <li class="sidebar-item">
                <a href="{{ route('applicants.index') }}" class="sidebar-link {{ Route::is('applicants.index') || (Route::is('applicants.show') && !Route::is('applicants.create')) ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i>
                    <span>Applicants List</span>
                </a>
            </li>

            <li class="sidebar-item-header mt-3 mb-2 px-3 text-muted text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.8px; opacity: 0.8;">Entrance Exams</li>
            <li class="sidebar-item">
                <a href="{{ route('exams.subjects') }}" class="sidebar-link {{ Route::is('exams.subjects') ? 'active' : '' }}">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    <span>Exam Subjects</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('exams.scores') }}" class="sidebar-link {{ Route::is('exams.scores') ? 'active' : '' }}">
                    <i class="bi bi-card-checklist"></i>
                    <span>Batch Score Entry</span>
                </a>
            </li>

            @if(auth()->user()->hasRole(['Principal', 'Super Admin']))
            <li class="sidebar-item">
                <a href="{{ route('reports.index') }}" class="sidebar-link {{ Route::is('reports.index') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart-line-fill"></i>
                    <span>Admission Reports</span>
                </a>
            </li>
            @endif

            @if(auth()->user()->hasRole('Super Admin'))
            <li class="sidebar-item-header mt-4 mb-2 px-3 text-muted text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.8px;">Administration</li>
            <li class="sidebar-item">
                <a href="{{ route('settings.index') }}" class="sidebar-link {{ Route::is('settings.index') ? 'active' : '' }}">
                    <i class="bi bi-gear-fill"></i>
                    <span>Portal Settings</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('users.index') }}" class="sidebar-link {{ Route::is('users.index') ? 'active' : '' }}">
                    <i class="bi bi-person-gear"></i>
                    <span>System Users</span>
                </a>
            </li>
            @endif
        </ul>

        <!-- Bottom Session Brand -->
        <div class="p-3 border-top border-secondary border-opacity-10 text-center">
            <small class="text-secondary" style="font-size: 0.75rem;">Session: {{ \App\Models\Setting::get('school_name') ? \App\Models\AcademicSession::find(\App\Models\Setting::get('admission_current_session_id'))?->name : '2025/2026' }}</small>
        </div>
    </div>

    <!-- Wrapper -->
    <div class="wrapper">
        <!-- Topbar -->
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebarCollapse">
                <i class="bi bi-list"></i>
            </button>
            
            <div class="d-none d-md-flex align-items-center">
                <h5 class="m-0 fw-semibold text-secondary">{{ \App\Models\Setting::get('school_name', "St. Augustine's College, Ibusa") }}</h5>
            </div>

            <!-- Profile and Log Out -->
            <div class="dropdown">
                <div class="user-profile dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="d-none d-sm-block text-start">
                        <div class="fw-semibold" style="font-size: 0.88rem; line-height: 1.1;">{{ auth()->user()->name }}</div>
                        <span class="badge 
                            @if(auth()->user()->hasRole('Super Admin')) bg-danger 
                            @elseif(auth()->user()->hasRole('Principal')) bg-success 
                            @else bg-primary 
                            @endif" style="font-size: 0.65rem;">
                            {{ auth()->user()->role->name }}
                        </span>
                    </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li><h6 class="dropdown-header text-muted">User Account</h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" id="logoutForm">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Sign Out</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Alert Notifications -->
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

            @yield('content')
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sidebar Toggle JS -->
    <script>
        document.getElementById('sidebarCollapse').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
    </script>
    @yield('scripts')
</body>
</html>
