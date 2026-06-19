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
        // Drop foreign keys that depend on the old unique index
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->dropForeign(['applicant_id']);
            $table->dropForeign(['exam_subject_id']);
        });

        // Drop the old unique index
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->dropUnique('exam_scores_applicant_id_exam_subject_id_unique');
        });

        // Create a new unique index that includes exam_batch
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->unique(['applicant_id', 'exam_subject_id', 'exam_batch'], 'exam_scores_applicant_subject_batch_unique');
        });

        // Recreate foreign keys
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->foreign('applicant_id')->references('id')->on('applicants')->onDelete('cascade');
            $table->foreign('exam_subject_id')->references('id')->on('exam_subjects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->dropForeign(['applicant_id']);
            $table->dropForeign(['exam_subject_id']);
        });

        Schema::table('exam_scores', function (Blueprint $table) {
            $table->dropUnique('exam_scores_applicant_subject_batch_unique');
        });

        // Restore old unique index
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->unique(['applicant_id', 'exam_subject_id'], 'exam_scores_applicant_id_exam_subject_id_unique');
        });

        // Recreate foreign keys
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->foreign('applicant_id')->references('id')->on('applicants')->onDelete('cascade');
            $table->foreign('exam_subject_id')->references('id')->on('exam_subjects')->onDelete('cascade');
        });
    }
};
