<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Applicant;
use App\Models\ExamSubject;
use App\Models\ExamScore;
use App\Models\Setting;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicResultTest extends TestCase
{
    use RefreshDatabase;

    private $session;
    private $applicantAdmitted;
    private $applicantPending;
    private $subjectMath;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed Roles & Permissions
        $superAdminRole = Role::create(['name' => 'Super Admin']);

        // 2. Create Current Academic Session
        $this->session = AcademicSession::create([
            'name' => '2025/2026',
            'is_current' => true
        ]);

        // 3. Create Settings
        Setting::create(['key' => 'school_name', 'value' => "St. Augustine's College, Ibusa", 'group' => 'school']);
        Setting::create(['key' => 'admission_current_session_id', 'value' => (string)$this->session->id, 'group' => 'admission']);
        Setting::create(['key' => 'admission_junior_cutoff', 'value' => '50', 'group' => 'admission']);
        Setting::create(['key' => 'admission_senior_cutoff', 'value' => '50', 'group' => 'admission']);
        Setting::create(['key' => 'admission_interview_date', 'value' => 'Saturday, July 18, 2026', 'group' => 'admission']);
        Setting::create(['key' => 'admission_letter_template', 'value' => "Dear {firstname},\n\nWe are pleased to offer you admission into St. Augustine's College, Ibusa for the {session} academic session in {class}.\n\nYour Registration Number is: {registration_number}\n\nCongratulations!", 'group' => 'admission']);

        // 4. Create Applicants
        $this->applicantAdmitted = Applicant::create([
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
            'admission_status' => 'Admitted',
            'academic_session_id' => $this->session->id,
        ]);

        $this->applicantPending = Applicant::create([
            'registration_number' => 'SAC-0002',
            'surname' => 'Eze',
            'first_name' => 'Chidi',
            'gender' => 'Male',
            'date_of_birth' => '2012-09-20',
            'state_of_origin' => 'Enugu',
            'lga' => 'Udi',
            'parent_phone_number' => '08030000000',
            'address' => 'No 12 Obu Street, Ibusa',
            'class_applying_for' => 'JSS1',
            'admission_status' => 'Pending',
            'academic_session_id' => $this->session->id,
        ]);

        // 5. Create Exam Subject & Scores
        $this->subjectMath = ExamSubject::create(['name' => 'Mathematics']);

        ExamScore::create([
            'applicant_id' => $this->applicantAdmitted->id,
            'exam_subject_id' => $this->subjectMath->id,
            'score' => 85,
        ]);

        ExamScore::create([
            'applicant_id' => $this->applicantPending->id,
            'exam_subject_id' => $this->subjectMath->id,
            'score' => 45,
        ]);
    }

    /**
     * Test result checker landing page loads successfully.
     */
    public function test_result_checker_landing_page_loads(): void
    {
        $response = $this->get(route('public.results.form'));
        $response->assertStatus(200);
        $response->assertSee('Check Your Result');
    }

    /**
     * Test result check redirect with valid details.
     */
    public function test_result_check_with_valid_details_redirects(): void
    {
        $response = $this->post(route('public.results.check'), [
            'registration_number' => 'SAC-0001',
        ]);

        $response->assertRedirect(route('public.results.details'));
        $response->assertSessionHas('verified_result_applicant_id', $this->applicantAdmitted->id);
    }

    /**
     * Test result check with invalid details redirects back with error.
     */
    public function test_result_check_with_invalid_details_fails(): void
    {
        $response = $this->post(route('public.results.check'), [
            'registration_number' => 'SAC-9999',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Test access to result details page without verifying.
     */
    public function test_cannot_access_details_without_session(): void
    {
        $response = $this->get(route('public.results.details'));
        $response->assertRedirect(route('public.results.form'));
        $response->assertSessionHas('error');
    }

    /**
     * Test access to result details page with verified session.
     */
    public function test_can_access_details_with_session(): void
    {
        $response = $this->withSession(['verified_result_applicant_id' => $this->applicantAdmitted->id])
            ->get(route('public.results.details'));

        $response->assertStatus(200);
        $response->assertSee('Chukwuka Nwajei');
        $response->assertSee('SAC-0001');
        $response->assertSee('Mathematics');
        $response->assertSee('85');
    }

    /**
     * Test admitted applicant can download admission letter.
     */
    public function test_admitted_applicant_can_download_letter(): void
    {
        $response = $this->withSession(['verified_result_applicant_id' => $this->applicantAdmitted->id])
            ->get(route('public.results.letter', $this->applicantAdmitted->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test non-admitted applicant cannot download admission letter.
     */
    public function test_non_admitted_applicant_cannot_download_letter(): void
    {
        $response = $this->withSession(['verified_result_applicant_id' => $this->applicantPending->id])
            ->get(route('public.results.letter', $this->applicantPending->id));

        $response->assertStatus(403);
    }

    /**
     * Test that an applicant cannot download someone else's letter even if verified.
     */
    public function test_cannot_download_other_applicants_letter(): void
    {
        $response = $this->withSession(['verified_result_applicant_id' => $this->applicantAdmitted->id])
            ->get(route('public.results.letter', $this->applicantPending->id));

        $response->assertStatus(403);
    }

    /**
     * Test that a candidate meeting the cutoff score is automatically admitted upon viewing results.
     */
    public function test_candidate_meeting_cutoff_is_automatically_admitted(): void
    {
        // Create an applicant who is currently Pending but has a passing score (70)
        $passingApplicant = Applicant::create([
            'registration_number' => 'SAC-0003',
            'surname' => 'Ogun',
            'first_name' => 'Tunde',
            'gender' => 'Male',
            'date_of_birth' => '2012-04-10',
            'state_of_origin' => 'Ogun',
            'lga' => 'Abeokuta North',
            'parent_phone_number' => '08022222222',
            'address' => 'No 5 Ogun Road, Ibusa',
            'class_applying_for' => 'JSS1',
            'admission_status' => 'Pending',
            'academic_session_id' => $this->session->id,
        ]);

        ExamScore::create([
            'applicant_id' => $passingApplicant->id,
            'exam_subject_id' => $this->subjectMath->id,
            'score' => 70, // 70 >= 50
        ]);

        // Access details page
        $response = $this->withSession(['verified_result_applicant_id' => $passingApplicant->id])
            ->get(route('public.results.details'));

        $response->assertStatus(200);
        $response->assertSee('Status: Admitted');
        $response->assertSee('Oral Interview Invitation');
        $response->assertSee('Saturday, July 18, 2026');

        // Verify database is updated
        $this->assertEquals('Admitted', $passingApplicant->fresh()->admission_status);
    }

    /**
     * Test failed candidate can register for resit from public results page.
     */
    public function test_failed_candidate_can_register_for_resit_from_public_page(): void
    {
        // Setup failed applicant with low score
        $failedApplicant = Applicant::create([
            'registration_number' => 'SAC-0004',
            'surname' => 'Salami',
            'first_name' => 'Femi',
            'gender' => 'Male',
            'date_of_birth' => '2012-04-10',
            'state_of_origin' => 'Ogun',
            'lga' => 'Abeokuta North',
            'parent_phone_number' => '08022222222',
            'address' => 'No 5 Ogun Road, Ibusa',
            'class_applying_for' => 'JSS1',
            'admission_status' => 'Pending',
            'exam_batch' => 'Batch A',
            'academic_session_id' => $this->session->id,
        ]);

        ExamScore::create([
            'applicant_id' => $failedApplicant->id,
            'exam_subject_id' => $this->subjectMath->id,
            'score' => 30, // 30 < 50 cutoff
        ]);

        $response = $this->withSession(['verified_result_applicant_id' => $failedApplicant->id])
            ->post(route('public.results.resit', $failedApplicant->id));

        $response->assertRedirect(route('public.results.details'));
        $response->assertSessionHas('success');

        // Check applicant's status reset to Pending and batch updated to Batch A - Resit
        $this->assertEquals('Pending', $failedApplicant->fresh()->admission_status);
        $this->assertEquals('Batch A - Resit', $failedApplicant->fresh()->exam_batch);

        // Check scores cleared
        $this->assertEmpty($failedApplicant->fresh()->examScores);
    }

    /**
     * Test passed candidate cannot register for resit.
     */
    public function test_passed_candidate_cannot_register_for_resit_from_public_page(): void
    {
        // Setup passing applicant
        $passedApplicant = Applicant::create([
            'registration_number' => 'SAC-0005',
            'surname' => 'Audu',
            'first_name' => 'Ali',
            'gender' => 'Male',
            'date_of_birth' => '2012-04-10',
            'state_of_origin' => 'Ogun',
            'lga' => 'Abeokuta North',
            'parent_phone_number' => '08022222222',
            'address' => 'No 5 Ogun Road, Ibusa',
            'class_applying_for' => 'JSS1',
            'admission_status' => 'Pending',
            'exam_batch' => 'Batch A',
            'academic_session_id' => $this->session->id,
        ]);

        ExamScore::create([
            'applicant_id' => $passedApplicant->id,
            'exam_subject_id' => $this->subjectMath->id,
            'score' => 80, // 80 >= 50 cutoff
        ]);

        $response = $this->withSession(['verified_result_applicant_id' => $passedApplicant->id])
            ->post(route('public.results.resit', $passedApplicant->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Check database is unchanged
        $this->assertEquals('Batch A', $passedApplicant->fresh()->exam_batch);
    }

    /**
     * Test accessing public resit route without session verified aborts.
     */
    public function test_cannot_register_for_resit_without_session(): void
    {
        $applicant = Applicant::create([
            'registration_number' => 'SAC-0006',
            'surname' => 'Okafor',
            'first_name' => 'Obinna',
            'gender' => 'Male',
            'date_of_birth' => '2012-04-10',
            'state_of_origin' => 'Delta',
            'lga' => 'Oshimili North',
            'parent_phone_number' => '08022222222',
            'address' => 'No 5 Ibusa Road, Ibusa',
            'class_applying_for' => 'JSS1',
            'admission_status' => 'Pending',
            'exam_batch' => 'Batch A',
            'academic_session_id' => $this->session->id,
        ]);

        $response = $this->post(route('public.results.resit', $applicant->id));
        $response->assertStatus(403);
    }
    /**
     * Test that an applicant who passed the exam but is explicitly Rejected shows as Rejected.
     */
    public function test_passed_candidate_explicitly_rejected_shows_rejected(): void
    {
        $applicant = Applicant::create([
            'registration_number' => 'SAC-0007',
            'surname' => 'Adeyemi',
            'first_name' => 'Bayo',
            'gender' => 'Male',
            'date_of_birth' => '2012-04-10',
            'state_of_origin' => 'Ogun',
            'lga' => 'Abeokuta North',
            'parent_phone_number' => '08022222222',
            'address' => 'No 5 Ibusa Road, Ibusa',
            'class_applying_for' => 'JSS1',
            'admission_status' => 'Rejected',
            'academic_session_id' => $this->session->id,
        ]);

        ExamScore::create([
            'applicant_id' => $applicant->id,
            'exam_subject_id' => $this->subjectMath->id,
            'score' => 85, // Passing score (>= 50)
        ]);

        $response = $this->withSession(['verified_result_applicant_id' => $applicant->id])
            ->get(route('public.results.details'));

        $response->assertStatus(200);
        
        // Assert that the page explicitly shows the rejected status and message
        $response->assertSee('Status: Rejected');
        $response->assertSee('We regret to inform you that you have not been offered admission');
        
        // Assert that they cannot download the letter
        $response->assertDontSee('Download Admission Letter');
    }

    /**
     * Test that an applicant who failed the exam but has Admitted status shows as Failed.
     * This protects against auto-admissions that get stuck when scores are subsequently lowered.
     */
    public function test_failed_candidate_explicitly_admitted_shows_failed(): void
    {
        $applicant = Applicant::create([
            'registration_number' => 'SAC-0008',
            'surname' => 'Okoro',
            'first_name' => 'Chioma',
            'gender' => 'Female',
            'date_of_birth' => '2012-04-10',
            'state_of_origin' => 'Enugu',
            'lga' => 'Udi',
            'parent_phone_number' => '08022222222',
            'address' => 'No 5 Ibusa Road, Ibusa',
            'class_applying_for' => 'JSS1',
            'admission_status' => 'Admitted',
            'academic_session_id' => $this->session->id,
        ]);

        ExamScore::create([
            'applicant_id' => $applicant->id,
            'exam_subject_id' => $this->subjectMath->id,
            'score' => 30, // Failing score (< 50)
        ]);

        $response = $this->withSession(['verified_result_applicant_id' => $applicant->id])
            ->get(route('public.results.details'));

        $response->assertStatus(200);
        
        // Assert that the page prioritizes the failing score and shows Failed
        $response->assertSee('Status: Failed');
        $response->assertSee('We regret to inform you that you did not meet the minimum score requirement');
        
        // Assert that they CANNOT download the letter
        $response->assertDontSee('Download Admission Letter');
    }
}
