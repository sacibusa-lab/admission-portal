<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Display the login view.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']) . '|' . $request->ip());

        // Implement security: Rate limiting
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            AuditLogger::log('login', ['email' => $credentials['email']], Auth::id());

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($throttleKey, 60);

        AuditLogger::log('failed_login_attempt', ['email' => $credentials['email']]);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            AuditLogger::log('logout', ['email' => $user->email], $user->id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Display the forgot password view.
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle forgot password submission (Demo mode).
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        AuditLogger::log('forgot_password_request', ['email' => $request->email]);

        return back()->with('status', 'We have simulated emailing your password reset link!');
    }
}
