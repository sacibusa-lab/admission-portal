<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Applicant;
use App\Models\ExamSubject;
use App\Models\ExamScore;
use App\Models\Setting;
use App\Models\User;
use App\Models\Role;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicantResitTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $nonAdmin;
    private $session;
    private $subjectMath;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Roles
        $superAdminRole = Role::create(['name' => 'Super Admin']);
        $principalRole = Role::create(['name' => 'Principal']);

        // 2. Create Users
        $this->admin = new User();
        $this->admin->name = 'Admin User';
        $this->admin->email = 'admin@example.com';
        $this->admin->password = bcrypt('password123');
        $this->admin->role_id = $superAdminRole->id;
        $this->admin->save();

        $this->nonAdmin = new User();
        $this->nonAdmin->name = 'Principal User';
        $this->nonAdmin->email = 'principal@example.com';
        $this->nonAdmin->password = bcrypt('password123');
        $this->nonAdmin->role_id = $principalRole->id;
        $this->nonAdmin->save();

        // 3. Create Current Academic Session
        $this->session = AcademicSession::create([
            'name' => '2025/2026',
            'is_current' => true
        ]);

        // 4. Create Settings & Subjects
        Setting::create(['key' => 'admission_current_session_id', 'value' => (string)$this->session->id, 'group' => 'admission']);
        Setting::create(['key' => 'school_name', 'value' => "St. Augustine's College", 'group' => 'school']);
        
        $this->subjectMath = ExamSubject::create(['name' => 'Mathematics']);
    }

    /**
     * Guest cannot register an applicant for resit.
     */
    public function test_guest_cannot_register_resit(): void
    {
        $applicant = Applicant::create([
            'registration_number' => 'SAC-0001',
            'surname' => 'Chukwuka',
            'first_name' => 'Nwajei',
            'gender' => 'Male',
            'date_of_birth' => '2012-05-15',
            'state_of_origin' => 'Delta',
            'lga' => 'Oshimili North',
            'parent_phone_number' => '08162157107',
            'address' => 'No 1 College Road, Ibusa',
            'class_applying_for' => 'JSS1',
            'admission_status' => 'Failed',
            'exam_batch' => 'Batch A',
            'academic_session_id' => $this->session->id,
        ]);

        $response = $this->post(route('applicants.resit', $applicant->id));
        $response->assertRedirect(route('login'));
    }

    /**
     * User without correct role cannot register applicant for resit.
     */
    public function test_unauthorized_role_cannot_register_resit(): void
    {
        $applicant = Applicant::create([
            'registration_number' => 'SAC-0001',
            'surname' => 'Chukwuka',
            'first_name' => 'Nwajei',
            'gender' => 'Male',
            'date_of_birth' => '2012-05-15',
            'state_of_origin' => 'Delta',
            'lga' => 'Oshimili North',
            'parent_phone_number' => '08162157107',
            'address' => 'No 1 College Road, Ibusa',
            'class_applying_for' => 'JSS1',
            'admission_status' => 'Failed',
            'exam_batch' => 'Batch A',
            'academic_session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->nonAdmin)
            ->post(route('applicants.resit', $applicant->id));

        $response->assertStatus(403);
    }

    /**
     * Admin can register failed applicant for resit, clearing scores and queueing SMS.
     */
    public function test_admin_can_register_resit_for_failed_applicant(): void
    {
        $applicant = Applicant::create([
            'registration_number' => 'SAC-0001',
            'surname' => 'Chukwuka',
            'first_name' => 'Nwajei',
            'gender' => 'Male',
            'date_of_birth' => '2012-05-15',
            'state_of_origin' => 'Delta',
            'lga' => 'Oshimili North',
            'parent_phone_number' => '08162157107',
            'address' => 'No 1 College Road, Ibusa',
            'class_applying_for' => 'JSS1',
            'admission_status' => 'Failed',
            'exam_batch' => 'Batch A',
            'academic_session_id' => $this->session->id,
        ]);

        // Add a score
        ExamScore::create([
            'applicant_id' => $applicant->id,
            'exam_subject_id' => $this->subjectMath->id,
            'score' => 35
        ]);

        $this->assertDatabaseHas('exam_scores', [
            'applicant_id' => $applicant->id,
            'score' => 35
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('applicants.resit', $applicant->id));

        $response->assertRedirect(route('applicants.show', $applicant->id));
        $response->assertSessionHas('success');

        // Verify status and batch are updated in database
        $this->assertDatabaseHas('applicants', [
            'id' => $applicant->id,
            'exam_batch' => 'Batch A - Resit',
            'admission_status' => 'Pending'
        ]);

        // Verify scores are deleted
        $this->assertDatabaseMissing('exam_scores', [
            'applicant_id' => $applicant->id
        ]);

        // Verify SMS log contains the confirmation message
        $this->assertDatabaseHas('sms_logs', [
            'phone' => '08162157107',
            'status' => 'Sent (Mock)'
        ]);
        
        $smsLog = SmsLog::where('phone', '08162157107')->first();
        $this->assertStringContainsString('Batch A - Resit', $smsLog->message);
    }
}
