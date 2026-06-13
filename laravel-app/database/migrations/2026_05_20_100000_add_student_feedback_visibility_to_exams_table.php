<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->boolean('show_student_ai_feedback')->default(true)->after('results_released');
            $table->boolean('show_student_expected_answers')->default(true)->after('show_student_ai_feedback');
            $table->boolean('show_student_revision_suggestions')->default(true)->after('show_student_expected_answers');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->dropColumn([
                'show_student_ai_feedback',
                'show_student_expected_answers',
                'show_student_revision_suggestions',
            ]);
        });
    }
};
