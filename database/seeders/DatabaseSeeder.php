<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Setting;
use App\Models\AcademicSession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Roles
        $superAdminRole = Role::create(['name' => 'Super Admin']);
        $officerRole = Role::create(['name' => 'Admission Officer']);
        $principalRole = Role::create(['name' => 'Principal']);

        // 2. Seed Permissions
        $permissions = [
            'manage_users',
            'manage_admissions',
            'manage_settings',
            'view_reports',
            'register_applicants',
            'upload_documents',
            'view_applicants',
            'change_admission_status',
            'approve_admissions',
            'generate_letters',
            'print_reports'
        ];

        foreach ($permissions as $permName) {
            Permission::create(['name' => $permName]);
        }

        // 3. Associate Permissions to Roles
        // Officer Permissions
        $officerPerms = [
            'register_applicants',
            'upload_documents',
            'view_applicants',
            'change_admission_status'
        ];
        $officerRole->permissions()->attach(
            Permission::whereIn('name', $officerPerms)->pluck('id')
        );

        // Principal Permissions
        $principalPerms = [
            'view_applicants',
            'approve_admissions',
            'generate_letters',
            'print_reports',
            'view_reports'
        ];
        $principalRole->permissions()->attach(
            Permission::whereIn('name', $principalPerms)->pluck('id')
        );

        // 4. Seed Academic Sessions
        $currentSession = AcademicSession::create([
            'name' => '2025/2026',
            'is_current' => true
        ]);

        // 5. Seed Users
        User::create([
            'name' => 'System Administrator',
            'email' => 'admin@staugustine.edu.ng',
            'password' => Hash::make('password123'),
            'role_id' => $superAdminRole->id,
        ]);

        User::create([
            'name' => 'Admission Officer',
            'email' => 'officer@staugustine.edu.ng',
            'password' => Hash::make('password123'),
            'role_id' => $officerRole->id,
        ]);

        User::create([
            'name' => 'Principal Office',
            'email' => 'principal@staugustine.edu.ng',
            'password' => Hash::make('password123'),
            'role_id' => $principalRole->id,
        ]);

        // 6. Seed Configuration Settings
        $settings = [
            // School Settings
            ['key' => 'school_name', 'value' => "St. Augustine's College, Ibusa", 'group' => 'school'],
            ['key' => 'school_logo', 'value' => '', 'group' => 'school'],
            ['key' => 'school_address', 'value' => 'Ibusa, Delta State, Nigeria', 'group' => 'school'],
            ['key' => 'school_email', 'value' => 'info@staugustineibusa.com', 'group' => 'school'],
            ['key' => 'school_phone', 'value' => '+2348030000000', 'group' => 'school'],

            // Termii Settings
            ['key' => 'termii_api_key', 'value' => '', 'group' => 'termii'],
            ['key' => 'termii_sender_id', 'value' => 'SAC', 'group' => 'termii'],

            // OpenRouter Settings
            ['key' => 'openrouter_api_key', 'value' => '', 'group' => 'openrouter'],
            ['key' => 'openrouter_model', 'value' => 'google/gemini-2.5-flash', 'group' => 'openrouter'],

            // Admission Settings
            ['key' => 'admission_current_session_id', 'value' => (string)$currentSession->id, 'group' => 'admission'],
            ['key' => 'admission_prefix', 'value' => 'SAC', 'group' => 'admission'],
            ['key' => 'admission_junior_cutoff', 'value' => '50', 'group' => 'admission'],
            ['key' => 'admission_senior_cutoff', 'value' => '50', 'group' => 'admission'],
            ['key' => 'admission_interview_date', 'value' => 'Saturday, July 18, 2026', 'group' => 'admission'],
            ['key' => 'admission_letter_template', 'value' => "Dear {firstname},\n\nWe are pleased to offer you admission into St. Augustine's College, Ibusa for the {session} academic session in {class}.\n\nYour Registration Number is: {registration_number}\n\nCongratulations!\n\nSigned,\nPrincipal Office", 'group' => 'admission'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }

        // 7. Seed Default Exam Subjects
        $defaultSubjects = [
            ['name' => 'Mathematics'],
            ['name' => 'English Language'],
            ['name' => 'General Paper'],
        ];
        foreach ($defaultSubjects as $sub) {
            \App\Models\ExamSubject::create($sub);
        }

        // 8. Seed Default School Classes
        $defaultClasses = [
            ['name' => 'JSS1'],
            ['name' => 'JSS2'],
            ['name' => 'JSS3'],
            ['name' => 'SS1'],
            ['name' => 'SS2'],
            ['name' => 'SS3'],
        ];
        foreach ($defaultClasses as $cls) {
            \App\Models\SchoolClass::create($cls);
        }
    }
}
