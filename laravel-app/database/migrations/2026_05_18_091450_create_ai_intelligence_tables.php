<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('learning_twins')) {
            Schema::create('learning_twins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lecturer_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('student_index', 64);
                $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
                $table->string('context_key', 64)->default('all');
                $table->unsignedBigInteger('source_attempt_id')->nullable();
                $table->json('input_snapshot')->nullable();
                $table->unsignedTinyInteger('academic_health_score')->nullable();
                $table->string('risk_level', 32)->nullable();
                $table->unsignedSmallInteger('predicted_next_score')->nullable();
                $table->json('strengths')->nullable();
                $table->json('weaknesses')->nullable();
                $table->text('learning_pattern')->nullable();
                $table->json('recommended_actions')->nullable();
                $table->json('revision_focus')->nullable();
                $table->text('lecturer_intervention')->nullable();
                $table->json('raw_ai_response')->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();

                $table->unique(['lecturer_user_id', 'student_index', 'context_key']);
                $table->index(['lecturer_user_id', 'student_index']);
            });
        }

        if (! Schema::hasTable('prediction_reports')) {
            Schema::create('prediction_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lecturer_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('student_index', 64);
                $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
                $table->json('student_data_snapshot');
                $table->unsignedTinyInteger('failure_probability')->nullable();
                $table->string('dropout_risk', 32)->nullable();
                $table->string('burnout_risk', 32)->nullable();
                $table->string('predicted_grade', 8)->nullable();
                $table->string('intervention_urgency', 32)->nullable();
                $table->json('recommended_interventions')->nullable();
                $table->text('advisory_rationale')->nullable();
                $table->json('raw_ai_response')->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();

                $table->index(['lecturer_user_id', 'student_index']);
                $table->index(['lecturer_user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('analytics_reports')) {
            Schema::create('analytics_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('scope', 32)->default('course'); // course|department|institutional
                $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
                $table->string('course_name', 200)->nullable();
                $table->string('department_name', 200)->nullable();
                $table->string('context_key', 64)->default('default');
                $table->json('cohort_students_snapshot')->nullable();
                $table->unsignedTinyInteger('course_health_score')->nullable();
                $table->json('at_risk_students')->nullable();
                $table->json('weak_topics')->nullable();
                $table->json('performance_clusters')->nullable();
                $table->json('interventions')->nullable();
                $table->json('summary')->nullable();
                $table->json('department_insights')->nullable();
                $table->json('cohort_trends')->nullable();
                $table->json('raw_ai_response')->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();

                $table->index(['owner_user_id', 'scope', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_reports');
        Schema::dropIfExists('prediction_reports');
        Schema::dropIfExists('learning_twins');
    }
};
