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

        // Drop the old unique index — resolve its actual name dynamically
        $oldIndex = $this->getUniqueIndexName('exam_scores', ['applicant_id', 'exam_subject_id']);
        if ($oldIndex) {
            DB::statement("ALTER TABLE `exam_scores` DROP INDEX `{$oldIndex}`");
        }

        // Create a new unique index that includes exam_batch
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->unique(['applicant_id', 'exam_subject_id', 'exam_batch'], 'exam_scores_applicant_subject_batch_unique');
        });

        // Recreate foreign keys using raw SQL to avoid closure scope issues
        $fkName1 = $fkApplicant ?: 'exam_scores_applicant_id_foreign';
        $fkName2 = $fkSubject ?: 'exam_scores_exam_subject_id_foreign';
        DB::statement("ALTER TABLE `exam_scores` ADD CONSTRAINT `{$fkName1}` FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE");
        DB::statement("ALTER TABLE `exam_scores` ADD CONSTRAINT `{$fkName2}` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects`(`id`) ON DELETE CASCADE");
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

        // Drop the new index
        $newIndex = $this->getUniqueIndexName('exam_scores', ['applicant_id', 'exam_subject_id', 'exam_batch']);
        if ($newIndex) {
            DB::statement("ALTER TABLE `exam_scores` DROP INDEX `{$newIndex}`");
        } else {
            Schema::table('exam_scores', function (Blueprint $table) {
                $table->dropUnique('exam_scores_applicant_subject_batch_unique');
            });
        }

        // Restore old unique index
        DB::statement("ALTER TABLE `exam_scores` ADD UNIQUE INDEX `exam_scores_applicant_id_exam_subject_id_unique` (`applicant_id`, `exam_subject_id`)");

        // Recreate foreign keys using raw SQL to avoid closure scope issues
        $fkName1 = $fkApplicant ?: 'exam_scores_applicant_id_foreign';
        $fkName2 = $fkSubject ?: 'exam_scores_exam_subject_id_foreign';
        DB::statement("ALTER TABLE `exam_scores` ADD CONSTRAINT `{$fkName1}` FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE");
        DB::statement("ALTER TABLE `exam_scores` ADD CONSTRAINT `{$fkName2}` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects`(`id`) ON DELETE CASCADE");
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

    /**
     * Look up the actual unique index name covering the given columns.
     */
    private function getUniqueIndexName(string $table, array $columns): ?string
    {
        $placeholders = rtrim(str_repeat('?,', count($columns)), ',');
        $bindings = array_merge([DB::getDatabaseName(), $table], $columns);

        $row = DB::selectOne("
            SELECT INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME   = ?
              AND COLUMN_NAME IN ({$placeholders})
              AND NON_UNIQUE = 0
            GROUP BY INDEX_NAME
            HAVING COUNT(DISTINCT COLUMN_NAME) = ?
        ", array_merge($bindings, [count($columns)]));

        return $row?->INDEX_NAME;
    }
};
