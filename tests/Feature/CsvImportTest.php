<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Applicant;
use App\Models\Setting;
use App\Models\User;
use App\Models\Role;
use App\Models\SchoolClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $session;

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

        // 2. Create Current Academic Session
        $this->session = AcademicSession::create([
            'name' => '2025/2026',
            'is_current' => true
        ]);

        // 3. Create Settings & Class
        Setting::create(['key' => 'admission_current_session_id', 'value' => (string)$this->session->id, 'group' => 'admission']);
        Setting::create(['key' => 'school_name', 'value' => "St. Augustine's College", 'group' => 'school']);
        SchoolClass::create(['name' => 'JSS1']);
    }

    /**
     * CSV import parses and saves ExamBatch when provided.
     */
    public function test_csv_import_saves_exam_batch_when_provided(): void
    {
        $csvContent = "Surname,Firstname,ParentPhone,Class,ExamBatch\n" .
                      "Alabi,John,08037654321,JSS1,Batch B\n" .
                      "Chidi,Jane,07057654321,JSS1,Resit\n";

        $file = UploadedFile::fake()->createWithContent('applicants.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post(route('applicants.import.store'), [
                'csv_file' => $file
            ]);

        $response->assertRedirect(route('applicants.import'));
        
        $this->assertDatabaseHas('applicants', [
            'surname' => 'Alabi',
            'first_name' => 'John',
            'exam_batch' => 'Batch B',
            'class_applying_for' => 'JSS1'
        ]);

        $this->assertDatabaseHas('applicants', [
            'surname' => 'Chidi',
            'first_name' => 'Jane',
            'exam_batch' => 'Resit',
            'class_applying_for' => 'JSS1'
        ]);
    }

    /**
     * CSV import defaults to Batch A when ExamBatch column is not in CSV.
     */
    public function test_csv_import_defaults_to_batch_a_when_column_omitted(): void
    {
        $csvContent = "Surname,Firstname,ParentPhone,Class\n" .
                      "Alabi,John,08037654321,JSS1\n";

        $file = UploadedFile::fake()->createWithContent('applicants.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post(route('applicants.import.store'), [
                'csv_file' => $file
            ]);

        $response->assertRedirect(route('applicants.import'));
        
        $this->assertDatabaseHas('applicants', [
            'surname' => 'Alabi',
            'first_name' => 'John',
            'exam_batch' => 'Batch A'
        ]);
    }
}
