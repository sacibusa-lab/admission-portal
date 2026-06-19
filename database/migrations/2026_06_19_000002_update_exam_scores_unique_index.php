<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Dynamically resolve foreign key names (they may differ across environments)
        $fkApplicant = $this->getForeignKeyName('exam_scores', 'applicant_id');
        $fkSubject   = $this->getForeignKeyName('exam_scores', 'exam_subject_id');

        // Drop foreign keys first (they block index changes)
        if ($fkApplicant) {
            DB::statement("ALTER TABLE `exam_scores` DROP FOREIGN KEY `{$fkApplicant}`");
        }
        if ($fkSubject) {
            DB::statement("ALTER TABLE `exam_scores` DROP FOREIGN KEY `{$fkSubject}`");
        }

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
            $table->foreign('applicant_id', $fkApplicant ?: 'exam_scores_applicant_id_foreign')
                  ->references('id')->on('applicants')->onDelete('cascade');
            $table->foreign('exam_subject_id', $fkSubject ?: 'exam_scores_exam_subject_id_foreign')
                  ->references('id')->on('exam_subjects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $fkApplicant = $this->getForeignKeyName('exam_scores', 'applicant_id');
        $fkSubject   = $this->getForeignKeyName('exam_scores', 'exam_subject_id');

        if ($fkApplicant) {
            DB::statement("ALTER TABLE `exam_scores` DROP FOREIGN KEY `{$fkApplicant}`");
        }
        if ($fkSubject) {
            DB::statement("ALTER TABLE `exam_scores` DROP FOREIGN KEY `{$fkSubject}`");
        }

        Schema::table('exam_scores', function (Blueprint $table) {
            $table->dropUnique('exam_scores_applicant_subject_batch_unique');
        });

        // Restore old unique index
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->unique(['applicant_id', 'exam_subject_id'], 'exam_scores_applicant_id_exam_subject_id_unique');
        });

        // Recreate foreign keys
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->foreign('applicant_id', $fkApplicant ?: 'exam_scores_applicant_id_foreign')
                  ->references('id')->on('applicants')->onDelete('cascade');
            $table->foreign('exam_subject_id', $fkSubject ?: 'exam_scores_exam_subject_id_foreign')
                  ->references('id')->on('exam_subjects')->onDelete('cascade');
        });
    }

    /**
     * Look up the actual foreign key constraint name for a column.
     */
    private function getForeignKeyName(string $table, string $column): ?string
    {
        $row = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME   = ?
              AND COLUMN_NAME  = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [DB::getDatabaseName(), $table, $column]);

        return $row?->CONSTRAINT_NAME;
    }
};
