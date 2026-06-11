<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\AdmissionStatusController;
use App\Http\Controllers\AdmissionLetterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ExamScoreController;
use App\Http\Controllers\PublicResultController;
use App\Http\Controllers\SmsController;

// 1. Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
});

// 2. Public Result Checking Routes
Route::get('/check-results', [PublicResultController::class, 'showForm'])->name('public.results.form');
Route::post('/check-results', [PublicResultController::class, 'check'])->name('public.results.check');
Route::get('/check-results/details', [PublicResultController::class, 'showDetails'])->name('public.results.details');
Route::get('/check-results/letter/{id}', [PublicResultController::class, 'downloadLetter'])->name('public.results.letter');
Route::post('/check-results/resit/{id}', [PublicResultController::class, 'registerResit'])->name('public.results.resit');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('public.results.form');
})->name('home');

// 3. Authenticated Portal Routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // General applicant access (viewing profiles is available to all roles)
    Route::get('/applicants', [ApplicantController::class, 'index'])->name('applicants.index');
        // Live search suggestions endpoint
        Route::get('/applicants/search-suggestions', [ApplicantController::class, 'searchSuggestions'])->name('applicants.searchSuggestions');
    Route::get('/applicants/{id}', [ApplicantController::class, 'show'])->name('applicants.show');
    Route::get('/applicants/{id}/print-slip', [ApplicantController::class, 'printSlip'])->name('applicants.slip');

    // Exam routes viewable by all roles
    Route::get('/exams/subjects', [ExamScoreController::class, 'subjectsIndex'])->name('exams.subjects');
    Route::get('/exams/scores', [ExamScoreController::class, 'scoresIndex'])->name('exams.scores');
    Route::get('/exams/scores/export/csv', [ExamScoreController::class, 'exportScoresCsv'])->name('exams.scores.export.csv');
    Route::get('/exams/scores/export/pdf', [ExamScoreController::class, 'exportScoresPdf'])->name('exams.scores.export.pdf');

    // Super Admin + Admission Officer ONLY: Register, upload, process documents
    Route::middleware('role:Super Admin,Admission Officer')->group(function () {
        Route::get('/register', [ApplicantController::class, 'create'])->name('applicants.create');
        Route::post('/applicants', [ApplicantController::class, 'store'])->name('applicants.store');
        Route::get('/applicants/{id}/edit', [ApplicantController::class, 'edit'])->name('applicants.edit');
        Route::put('/applicants/{id}', [ApplicantController::class, 'update'])->name('applicants.update');
        Route::delete('/applicants/{id}', [ApplicantController::class, 'destroy'])->name('applicants.destroy');
        Route::post('/applicants/{id}/resit', [ApplicantController::class, 'registerResit'])->name('applicants.resit');

        // CSV Import
        Route::get('/import', [CsvImportController::class, 'showImportForm'])->name('applicants.import');
        Route::post('/import', [CsvImportController::class, 'import'])->name('applicants.import.store');
        Route::get('/import/errors', [CsvImportController::class, 'downloadErrorReport'])->name('applicants.import.errors');
        Route::get('/import/sample', [CsvImportController::class, 'downloadSample'])->name('applicants.import.sample');

        // OCR document processing
        Route::post('/ocr/process', [OcrController::class, 'process'])->name('ocr.process');
        Route::post('/ocr/scoresheet', [OcrController::class, 'processScoresheet'])->name('ocr.scoresheet');
        
        // Changing states (Pending -> Review -> Exam -> Pass/Fail)
        Route::post('/applicants/{id}/status', [AdmissionStatusController::class, 'update'])->name('applicants.status.update');

        // Exam creation & scores editing
        Route::post('/exams/subjects', [ExamScoreController::class, 'subjectsStore'])->name('exams.subjects.store');
        Route::delete('/exams/subjects/{id}', [ExamScoreController::class, 'subjectsDestroy'])->name('exams.subjects.destroy');
        Route::post('/exams/scores', [ExamScoreController::class, 'scoresStore'])->name('exams.scores.store');

        // SMS logs and batch messaging
        Route::get('/sms', [SmsController::class, 'index'])->name('sms.index');
        Route::post('/sms/{id}/resend', [SmsController::class, 'resend'])->name('sms.resend');
        Route::get('/sms/batch', [SmsController::class, 'showBatchForm'])->name('sms.batch.form');
        Route::post('/sms/batch', [SmsController::class, 'sendBatch'])->name('sms.batch.send');
    });

    // Principal + Super Admin + Admission Officer (with status updates checking roles internally)
    Route::middleware('role:Super Admin,Principal')->group(function () {
        // Report exports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
        Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        
        // Generating admission letters
        Route::get('/applicants/{id}/letter', [AdmissionLetterController::class, 'show'])->name('letters.show');
        Route::get('/applicants/{id}/letter/pdf', [AdmissionLetterController::class, 'downloadPdf'])->name('letters.pdf');
    });

    // Super Admin ONLY: Settings and System Users management
    Route::middleware('role:Super Admin')->group(function () {
        // Configuration
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/session', [SettingController::class, 'storeSession'])->name('settings.session.store');
        Route::post('/settings/session/{id}/current', [SettingController::class, 'setCurrentSession'])->name('settings.session.current');
        Route::post('/settings/class', [SettingController::class, 'storeClass'])->name('settings.class.store');
        Route::delete('/settings/class/{id}', [SettingController::class, 'destroyClass'])->name('settings.class.destroy');

        // User Account CRUD
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
