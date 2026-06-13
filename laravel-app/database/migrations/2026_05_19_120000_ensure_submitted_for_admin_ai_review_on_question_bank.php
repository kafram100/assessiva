<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026_05_16_140000 runs before question_bank exists, so it never added this column.
 * This migration fixes databases that created question_bank via 2026_05_18_091100
 * without submitted_for_admin_ai_review (the model always mass-assigns it).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('question_bank')) {
            return;
        }

        if (! Schema::hasColumn('question_bank', 'submitted_for_admin_ai_review')) {
            Schema::table('question_bank', function (Blueprint $table): void {
                $table->boolean('submitted_for_admin_ai_review')->default(false);
            });
        }

        if (! Schema::hasIndex('question_bank', 'qb_admin_ai_review_idx')) {
            Schema::table('question_bank', function (Blueprint $table): void {
                $table->index(['submitted_for_admin_ai_review', 'generated_by_ai', 'status'], 'qb_admin_ai_review_idx');
            });
        }

        if (Schema::hasTable('app_settings')) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => 'require_admin_approval_for_ai_questions'],
                ['value' => '0', 'updated_at' => now()],
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('question_bank')) {
            return;
        }

        if (Schema::hasIndex('question_bank', 'qb_admin_ai_review_idx')) {
            Schema::table('question_bank', function (Blueprint $table): void {
                $table->dropIndex('qb_admin_ai_review_idx');
            });
        }

        if (Schema::hasColumn('question_bank', 'submitted_for_admin_ai_review')) {
            Schema::table('question_bank', function (Blueprint $table): void {
                $table->dropColumn('submitted_for_admin_ai_review');
            });
        }

        if (Schema::hasTable('app_settings')) {
            DB::table('app_settings')->where('key', 'require_admin_approval_for_ai_questions')->delete();
        }
    }
};
