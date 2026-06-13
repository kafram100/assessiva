<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('revision_plans')) {
            return;
        }

        Schema::create('revision_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('exam_attempt_id')->constrained('attempts')->cascadeOnDelete();
            $table->string('student_index', 64);
            $table->string('student_name', 150)->nullable();
            $table->string('student_email', 150)->nullable();
            $table->string('course_name', 200)->nullable();
            $table->decimal('overall_score', 8, 2)->nullable();
            $table->string('risk_level', 32)->nullable();
            $table->json('weak_topics')->nullable();
            $table->json('failed_questions')->nullable();
            $table->text('summary')->nullable();
            $table->json('revision_notes')->nullable();
            $table->json('flashcards')->nullable();
            $table->json('practice_questions')->nullable();
            $table->json('study_plan')->nullable();
            $table->json('recommended_actions')->nullable();
            $table->string('ai_provider', 64)->nullable();
            $table->json('raw_ai_response')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique('exam_attempt_id');
            $table->index('exam_id');
            $table->index('student_index');
            $table->index(['exam_id', 'student_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revision_plans');
    }
};
