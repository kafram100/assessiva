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
        Schema::dropIfExists('official_question_reviews');

        Schema::table('question_bank', function (Blueprint $table) {
            if (Schema::hasColumn('question_bank', 'approved_by')) {
                $table->dropForeign(['approved_by']);
            }
            if (Schema::hasColumn('question_bank', 'official_approved_by')) {
                $table->dropForeign(['official_approved_by']);
            }
            foreach ([
                'question_bank_lecturer_id_status_index',
                'question_bank_department_id_status_index',
                'qb_admin_ai_review_idx',
            ] as $idx) {
                if (Schema::hasIndex('question_bank', $idx)) {
                    $table->dropIndex($idx);
                }
            }
            $table->dropColumn([
                'status',
                'visibility',
                'approved_by',
                'official_approved_by',
                'rejection_reason',
                'submitted_for_admin_ai_review',
            ]);
            $table->boolean('is_active')->default(true)->after('generated_by_ai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->string('status')->default('draft');
            $table->string('visibility')->default('personal');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('official_approved_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('submitted_for_admin_ai_review')->default(false);
        });
    }
};
