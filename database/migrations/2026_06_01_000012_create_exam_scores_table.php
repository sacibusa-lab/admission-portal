<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            $table->foreignId('exam_subject_id')->constrained('exam_subjects')->onDelete('cascade');
            $table->integer('score')->nullable();
            $table->timestamps();

            // Ensure unique combination of applicant and subject
            $table->unique(['applicant_id', 'exam_subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_scores');
    }
};
