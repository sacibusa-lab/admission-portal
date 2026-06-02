<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique()->index();
            $table->string('surname');
            $table->string('first_name');
            $table->string('other_name')->nullable();
            $table->string('gender')->index();
            $table->date('date_of_birth');
            $table->string('state_of_origin');
            $table->string('lga');
            $table->string('nationality')->default('Nigerian');
            $table->string('parent_phone_number')->index();
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('class_applying_for')->index();
            $table->string('admission_status')->default('Pending')->index();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->onDelete('restrict');
            $table->string('passport_path')->nullable();
            $table->string('birth_certificate_path')->nullable();
            $table->string('school_result_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
