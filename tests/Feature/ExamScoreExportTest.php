<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Applicant;
use App\Models\ExamSubject;
use App\Models\ExamScore;
use App\Models\Setting;
use App\Models\User;
use App\Models\Role;
use App\Models\SchoolClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamScoreExportTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $session;
    private $subjectMath;
    private $classJss1;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Role
        $superAdminRole = Role::create(['name' => 'Super Admin']);

        // 2. Create User
        $this->admin = new User();
        $this->admin->name = 'Admin User';
        $this->admin->email = 'admin@example.com';
        $this->admin->password = bcrypt('password123');
        $this->admin->role_id = $superAdminRole->id;
        $this->admin->save();

        // 3. Create Session
        $this->session = AcademicSession::create([
            'name' => '2025/2026',
            'is_current' => true
        ]);

        // 4. Create Setting
        Setting::create(['key' => 'admission_current_session_id', 'value' => (string)$this->session->id, 'group' => 'admission']);
        Setting::create(['key' => 'school_name', 'value' => "St. Augustine's College, Ibusa", 'group' => 'school']);

        // 5. Create Class & Subject
        $this->classJss1 = SchoolClass::create(['name' => 'JSS1']);
        $this->subjectMath = ExamSubject::create(['name' => 'Mathematics']);

        // 6. Create Applicant with Score
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
            'admission_status' => 'Pending',
            'academic_session_id' => $this->session->id,
        ]);

        ExamScore::create([
            'applicant_id' => $applicant->id,
            'exam_subject_id' => $this->subjectMath->id,
            'score' => 88
        ]);
    }

    /**
     * Guests cannot download score exports.
     */
    public function test_guest_cannot_export_scores(): void
    {
        $csvResponse = $this->get(route('exams.scores.export.csv', [
            'class' => 'JSS1'
        ]));
        $csvResponse->assertRedirect(route('login'));

        $pdfResponse = $this->get(route('exams.scores.export.pdf', [
            'class' => 'JSS1'
        ]));
        $pdfResponse->assertRedirect(route('login'));
    }

    /**
     * Authenticated admin can download CSV scores.
     */
    public function test_authenticated_admin_can_export_csv(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('exams.scores.export.csv', [
                'class' => 'JSS1'
            ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('SAC-0001', $content);
        $this->assertStringContainsString('Chukwuka', $content);
        $this->assertStringContainsString('Nwajei', $content);
        $this->assertStringContainsString('88', $content);
    }

    /**
     * Authenticated admin can download PDF scores.
     */
    public function test_authenticated_admin_can_export_pdf(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('exams.scores.export.pdf', [
                'class' => 'JSS1'
            ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test that score exports filter by batch.
     */
    public function test_export_filters_by_specified_batch(): void
    {
        // Create an applicant in Batch B
        $batchBApplicant = Applicant::create([
            'registration_number' => 'SAC-0002',
            'surname' => 'Obi',
            'first_name' => 'Kene',
            'gender' => 'Male',
            'date_of_birth' => '2012-08-10',
            'state_of_origin' => 'Anambra',
            'lga' => 'Idemili North',
            'parent_phone_number' => '08055551234',
            'address' => 'No 10 Awka Road, Ibusa',
            'class_applying_for' => 'JSS1',
            'admission_status' => 'Pending',
            'exam_batch' => 'Batch B',
            'academic_session_id' => $this->session->id,
        ]);

        ExamScore::create([
            'applicant_id' => $batchBApplicant->id,
            'exam_subject_id' => $this->subjectMath->id,
            'score' => 75
        ]);

        // Export Batch A
        $responseBatchA = $this->actingAs($this->admin)
            ->get(route('exams.scores.export.csv', [
                'class' => 'JSS1',
                'batch' => 'Batch A'
            ]));
        $responseBatchA->assertStatus(200);
        
        ob_start();
        $responseBatchA->sendContent();
        $contentA = ob_get_clean();

        // Batch A should contain Chukwuka Nwajei (SAC-0001) but not Obi Kene (SAC-0002)
        $this->assertStringContainsString('SAC-0001', $contentA);
        $this->assertStringContainsString('Chukwuka', $contentA);
        $this->assertStringNotContainsString('SAC-0002', $contentA);
        $this->assertStringNotContainsString('Obi', $contentA);

        // Export Batch B
        $responseBatchB = $this->actingAs($this->admin)
            ->get(route('exams.scores.export.csv', [
                'class' => 'JSS1',
                'batch' => 'Batch B'
            ]));
        $responseBatchB->assertStatus(200);
        
        ob_start();
        $responseBatchB->sendContent();
        $contentB = ob_get_clean();

        // Batch B should contain Obi Kene (SAC-0002) but not Chukwuka Nwajei (SAC-0001)
        $this->assertStringContainsString('SAC-0002', $contentB);
        $this->assertStringContainsString('Obi', $contentB);
        $this->assertStringNotContainsString('SAC-0001', $contentB);
        $this->assertStringNotContainsString('Chukwuka', $contentB);
    }
}
