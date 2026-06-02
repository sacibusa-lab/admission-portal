<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\Applicant;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    /**
     * Display settings tabs.
     */
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $sessions = AcademicSession::orderBy('name', 'desc')->get();
        $classes = SchoolClass::orderBy('name', 'asc')->get();
        return view('settings.index', compact('settings', 'sessions', 'classes'));
    }

    /**
     * Update settings keys.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            // School Branding
            'school_name' => 'required|string|max:255',
            'school_address' => 'required|string',
            'school_email' => 'required|email|max:255',
            'school_phone' => 'required|string|max:50',
            // Termii
            'termii_api_key' => 'nullable|string|max:255',
            'termii_sender_id' => 'required|string|max:50',
            // OpenRouter
            'openrouter_api_key' => 'nullable|string|max:255',
            'openrouter_model' => 'required|string|max:255',
            // Admission Config
            'admission_prefix' => 'required|string|max:20',
            'admission_letter_template' => 'required|string',
            'admission_junior_cutoff' => 'required|integer|min:0|max:100',
            'admission_senior_cutoff' => 'required|integer|min:0|max:100',
            'admission_interview_date' => 'required|string|max:255',
        ]);

        foreach ($data as $key => $value) {
            // Update or create setting key
            $group = 'general';
            if (str_starts_with($key, 'school_')) {
                $group = 'school';
            } elseif (str_starts_with($key, 'termii_')) {
                $group = 'termii';
            } elseif (str_starts_with($key, 'openrouter_')) {
                $group = 'openrouter';
            } elseif (str_starts_with($key, 'admission_')) {
                $group = 'admission';
            }

            Setting::set($key, $value, $group);
        }

        // Flush cached stats since the prefix or session may have changed
        Cache::forget('dashboard_stats');

        AuditLogger::log('update_settings', ['keys_updated' => array_keys($data)]);

        return redirect()->back()->with('success', 'System settings updated successfully.');
    }

    /**
     * Create a new academic session.
     */
    public function storeSession(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:academic_sessions,name|max:50',
        ]);

        $session = AcademicSession::create([
            'name' => $request->name,
            'is_current' => false
        ]);

        AuditLogger::log('create_academic_session', ['session_name' => $session->name]);

        return redirect()->back()->with('success', 'Academic session registered successfully.');
    }

    /**
     * Set the current active academic session.
     */
    public function setCurrentSession($id)
    {
        $session = AcademicSession::findOrFail($id);

        // Reset others and activate selected
        AcademicSession::where('is_current', true)->update(['is_current' => false]);
        $session->update(['is_current' => true]);

        Setting::set('admission_current_session_id', (string)$session->id, 'admission');

        // Reset statistics cache
        Cache::forget('dashboard_stats');

        AuditLogger::log('set_current_session', ['session_name' => $session->name]);

        return redirect()->back()->with('success', "Academic session {$session->name} set as the current active session.");
    }

    /**
     * Create a new school class.
     */
    public function storeClass(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:school_classes,name|max:50',
        ]);

        $class = SchoolClass::create([
            'name' => strtoupper(trim($request->name)),
        ]);

        AuditLogger::log('create_school_class', ['class_name' => $class->name]);

        return redirect()->back()->with('success', "School class '{$class->name}' registered successfully.");
    }

    /**
     * Delete a school class.
     */
    public function destroyClass($id)
    {
        $class = SchoolClass::findOrFail($id);

        // Check if there are any applicants registered for this class
        $applicantCount = Applicant::where('class_applying_for', $class->name)->count();
        if ($applicantCount > 0) {
            return redirect()->back()->with('error', "Cannot delete class '{$class->name}' because there are {$applicantCount} applicant(s) applying for it.");
        }

        $class->delete();

        AuditLogger::log('delete_school_class', ['class_name' => $class->name]);

        return redirect()->back()->with('success', "School class '{$class->name}' deleted successfully.");
    }
}
