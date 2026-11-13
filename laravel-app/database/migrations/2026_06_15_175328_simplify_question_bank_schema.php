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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('official_question_reviews');

        $isSqlite = DB::getDriverName() === 'sqlite';
        $existingForeignKeys = [];
        if (!$isSqlite) {
            try {
                $rows = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'question_bank' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
                $existingForeignKeys = array_map(fn ($r) => is_object($r) ? $r->CONSTRAINT_NAME : $r['CONSTRAINT_NAME'], $rows);
            } catch (\Throwable $e) {}
        }

        Schema::table('question_bank', function (Blueprint $table) use ($existingForeignKeys, $isSqlite) {
            if ($isSqlite) {
                $table->dropForeign(['approved_by']);
                $table->dropForeign(['official_approved_by']);
            } else {
                if (in_array('question_bank_approved_by_foreign', $existingForeignKeys, true)) {
                    $table->dropForeign(['approved_by']);
                }
                if (in_array('question_bank_official_approved_by_foreign', $existingForeignKeys, true)) {
                    $table->dropForeign(['official_approved_by']);
                }
                if (Schema::hasColumn('question_bank', 'lecturer_id') && !Schema::hasIndex('question_bank', ['lecturer_id'])) {
                    $table->index('lecturer_id');
                }
                if (Schema::hasColumn('question_bank', 'department_id') && !Schema::hasIndex('question_bank', ['department_id'])) {
                    $table->index('department_id');
                }
            }

            foreach ([
                'question_bank_approved_by_foreign',
                'question_bank_official_approved_by_foreign',
                'question_bank_lecturer_id_status_index',
                'question_bank_department_id_status_index',
                'qb_admin_ai_review_idx',
            ] as $idx) {
                if (Schema::hasIndex('question_bank', $idx)) {
                    $table->dropIndex($idx);
                }
            }

            $columnsToDrop = array_filter([
                'status',
                'visibility',
                'approved_by',
                'official_approved_by',
                'rejection_reason',
                'submitted_for_admin_ai_review',
            ], fn ($col) => Schema::hasColumn('question_bank', $col));

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            if (!Schema::hasColumn('question_bank', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('generated_by_ai');
            }
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            if (Schema::hasColumn('question_bank', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
