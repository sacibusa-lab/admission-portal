<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Applicant;
use App\Models\ExamSubject;
use App\Models\Setting;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OcrScoresheetScanTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $session;
    private $subjectMath;
    private $subjectEnglish;
    private $applicant;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Role & User
        $superAdminRole = Role::create(['name' => 'Super Admin']);
        $this->admin = new User();
        $this->admin->name = 'Admin User';
        $this->admin->email = 'admin@example.com';
        $this->admin->password = bcrypt('password123');
        $this->admin->role_id = $superAdminRole->id;
        $this->admin->save();

        // 2. Create Session
        $this->session = AcademicSession::create([
            'name' => '2025/2026',
            'is_current' => true
        ]);

        // 3. Create Settings
        Setting::create(['key' => 'admission_current_session_id', 'value' => (string)$this->session->id, 'group' => 'admission']);
        Setting::create(['key' => 'school_name', 'value' => "St. Augustine's College", 'group' => 'school']);

        // 4. Create Subjects
        $this->subjectMath = ExamSubject::create(['name' => 'Mathematics']);
        $this->subjectEnglish = ExamSubject::create(['name' => 'English Language']);

        // 5. Create Applicant
        $this->applicant = Applicant::create([
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
    }

    /**
     * Guest cannot access the OCR scoresheet scanning endpoint.
     */
    public function test_guest_cannot_scan_scoresheet(): void
    {
        $file = UploadedFile::fake()->create('scoresheet.pdf', 100, 'application/pdf');

        $response = $this->post(route('ocr.scoresheet'), [
            'document' => $file,
            'expected_students' => json_encode([['id' => $this->applicant->id, 'name' => $this->applicant->full_name, 'registration_number' => $this->applicant->registration_number]]),
            'expected_subjects' => json_encode([['id' => $this->subjectMath->id, 'name' => $this->subjectMath->name]]),
        ]);

        $response->assertRedirect(route('login'));
    }

    /**
     * Authenticated admin can successfully scan scoresheet (which runs mock mode when API key is empty).
     */
    public function test_admin_can_scan_scoresheet_and_extract_multi_subject_scores(): void
    {
        $file = UploadedFile::fake()->create('scoresheet.pdf', 100, 'application/pdf');

        $expectedStudents = [
            [
                'id' => $this->applicant->id,
                'name' => $this->applicant->full_name,
                'registration_number' => $this->applicant->registration_number
            ]
        ];

        $expectedSubjects = [
            [
                'id' => $this->subjectMath->id,
                'name' => $this->subjectMath->name
            ],
            [
                'id' => $this->subjectEnglish->id,
                'name' => $this->subjectEnglish->name
            ]
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('ocr.scoresheet'), [
                'document' => $file,
                'expected_students' => json_encode($expectedStudents),
                'expected_subjects' => json_encode($expectedSubjects),
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'SAC-0001' => [
                    $this->subjectMath->id,
                    $this->subjectEnglish->id
                ]
            ],
            'mock'
        ]);

        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertTrue($responseData['mock']); // Runs in mock mode because API Key is empty in tests
        $this->assertIsInt($responseData['data']['SAC-0001'][$this->subjectMath->id]);
        $this->assertIsInt($responseData['data']['SAC-0001'][$this->subjectEnglish->id]);
    }
}
