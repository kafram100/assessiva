<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'is_public_practice')) {
                $table->boolean('is_public_practice')->default(false)->after('results_released');
            }
            if (! Schema::hasColumn('exams', 'practice_mode')) {
                $table->boolean('practice_mode')->default(false)->after('is_public_practice');
            }
            if (! Schema::hasColumn('exams', 'allow_retry')) {
                $table->boolean('allow_retry')->default(true)->after('practice_mode');
            }
            if (! Schema::hasColumn('exams', 'show_answers_after_submit')) {
                $table->boolean('show_answers_after_submit')->default(true)->after('allow_retry');
            }
            if (! Schema::hasColumn('exams', 'show_explanations')) {
                $table->boolean('show_explanations')->default(true)->after('show_answers_after_submit');
            }
            if (! Schema::hasColumn('exams', 'practice_duration_minutes')) {
                $table->unsignedSmallInteger('practice_duration_minutes')->nullable()->after('show_explanations');
            }
            if (! Schema::hasColumn('exams', 'target_school_id')) {
                $table->foreignId('target_school_id')->nullable()->after('practice_duration_minutes')->constrained('schools')->nullOnDelete();
            }
            if (! Schema::hasColumn('exams', 'target_department_id')) {
                $table->foreignId('target_department_id')->nullable()->after('target_school_id')->constrained('departments')->nullOnDelete();
            }
            if (! Schema::hasColumn('exams', 'target_program')) {
                $table->string('target_program', 120)->nullable()->after('target_department_id');
            }
            if (! Schema::hasColumn('exams', 'target_level')) {
                $table->string('target_level', 60)->nullable()->after('target_program');
            }
            if (! Schema::hasColumn('exams', 'visibility_status')) {
                $table->string('visibility_status', 32)->default('public')->after('target_level');
            }
            if (! Schema::hasColumn('exams', 'difficulty_level')) {
                $table->string('difficulty_level', 32)->nullable()->after('visibility_status');
            }
            if (! Schema::hasColumn('exams', 'ai_recommended')) {
                $table->boolean('ai_recommended')->default(false)->after('difficulty_level');
            }
            if (! Schema::hasColumn('exams', 'practice_topic')) {
                $table->string('practice_topic', 150)->nullable()->after('ai_recommended');
            }
            if (! Schema::hasColumn('exams', 'is_featured_practice')) {
                $table->boolean('is_featured_practice')->default(false)->after('practice_topic');
            }
        });

        if (! Schema::hasTable('practice_quiz_attempts')) {
            Schema::create('practice_quiz_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained('exams')->cascadeOnDelete();
                $table->string('student_name', 150)->nullable();
                $table->string('student_email', 150)->nullable();
                $table->string('student_index', 64)->nullable();
                $table->string('department', 120)->nullable();
                $table->decimal('score', 8, 2)->nullable();
                $table->unsignedInteger('total_marks')->default(0);
                $table->json('responses')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->json('weak_topics')->nullable();
                $table->json('ai_feedback')->nullable();
                $table->timestamps();

                $table->index(['quiz_id', 'submitted_at']);
                $table->index(['student_index']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_quiz_attempts');

        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'target_department_id')) {
                $table->dropConstrainedForeignId('target_department_id');
            }
            if (Schema::hasColumn('exams', 'target_school_id')) {
                $table->dropConstrainedForeignId('target_school_id');
            }
            foreach ([
                'is_featured_practice',
                'practice_topic',
                'ai_recommended',
                'difficulty_level',
                'visibility_status',
                'target_level',
                'target_program',
                'practice_duration_minutes',
                'show_explanations',
                'show_answers_after_submit',
                'allow_retry',
                'practice_mode',
                'is_public_practice',
            ] as $col) {
                if (Schema::hasColumn('exams', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
