<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Admission Portal</title>
    
    @if(\App\Models\Setting::get('school_favicon'))
    <link rel="shortcut icon" href="{{ asset(\App\Models\Setting::get('school_favicon')) }}" type="image/x-icon">
    <link rel="icon" href="{{ asset(\App\Models\Setting::get('school_favicon')) }}" type="image/x-icon">
    @endif

    <!-- Google Fonts (Outfit) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #f1f5f9;
        }

        .login-card {
            background-color: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            width: 100%;
            max-width: 440px;
            padding: 2.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo {
            font-size: 3rem;
            color: #ffc107;
            margin-bottom: 0.5rem;
        }

        .login-header h3 {
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.25rem;
            font-size: 1.5rem;
        }

        .login-header p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .form-label {
            font-weight: 500;
            color: #cbd5e1;
            font-size: 0.88rem;
        }

        .form-control {
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            background-color: rgba(15, 23, 42, 0.8);
            border-color: #0b5ed7;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(11, 94, 215, 0.25);
        }

        .btn-login {
            background-color: #0b5ed7;
            border-color: #0b5ed7;
            color: #ffffff;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.2s ease;
            margin-top: 1rem;
        }

        .btn-login:hover {
            background-color: #0d6efd;
            transform: translateY(-1px);
        }

        .back-link {
            font-size: 0.88rem;
            color: #cbd5e1;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #ffc107;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                @if(\App\Models\Setting::get('school_logo'))
                    <img src="{{ asset(\App\Models\Setting::get('school_logo')) }}" alt="Logo" style="height: 60px; object-fit: contain; margin-bottom: 0.5rem; border-radius: 6px;">
                @else
                    <i class="bi bi-shield-lock-fill"></i>
                @endif
            </div>
            <h3>Recover Password</h3>
            <p>Enter your email address to receive a recovery link.</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success border-0 bg-success bg-opacity-20 text-success alert-dismissible fade show mb-4" role="alert" style="font-size: 0.88rem;">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <!-- Email Address -->
            <div class="mb-4">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@staugustine.edu.ng">
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <button type="submit" class="btn btn-login mb-3">
                Send Reset Link
            </button>

            <div class="text-center">
                <a href="{{ route('login') }}" class="back-link">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Sign In</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
