<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_generation_requests')) {
            Schema::create('ai_generation_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('course_id')->nullable();
                $table->string('topic', 150)->nullable();
                $table->string('input_type', 30)->default('text');
                $table->string('source_file_path', 255)->nullable();
                $table->longText('source_content')->nullable();
                $table->unsignedInteger('requested_question_count')->default(1);
                $table->json('question_types')->nullable();
                $table->string('difficulty', 20)->nullable();
                $table->unsignedInteger('total_marks')->default(0);
                $table->text('learning_outcome')->nullable();
                $table->boolean('prompt_injection_detected')->default(false);
                $table->string('status', 30)->default('completed');
                $table->string('ai_provider', 30)->nullable();
                $table->string('ai_model', 80)->nullable();
                $table->unsignedInteger('generated_count')->default(0);
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('question_bank')) {
            Schema::create('question_bank', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id')->nullable();
                $table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->unsignedBigInteger('topic_id')->nullable();
                $table->string('topic', 150)->nullable();
                $table->text('question_text');
                $table->enum('question_type', ['mcq', 'fill_in', 'short_answer', 'theory']);
                $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
                $table->text('correct_answer')->nullable();
                $table->text('model_answer')->nullable();
                $table->text('marking_scheme')->nullable();
                $table->unsignedInteger('suggested_marks')->default(1);
                $table->boolean('generated_by_ai')->default(false);
                $table->decimal('ai_confidence', 5, 2)->nullable();
                $table->text('source_reference')->nullable();
                $table->enum('status', [
                    'draft',
                    'approved',
                    'rejected',
                    'archived',
                    'pending_official_approval',
                    'official',
                ])->default('draft');
                $table->enum('visibility', ['personal', 'department', 'official'])->default('personal');
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('official_approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('rejection_reason')->nullable();
                $table->unsignedInteger('version')->default(1);
                $table->unsignedBigInteger('parent_question_id')->nullable();
                $table->foreignId('ai_generation_request_id')->nullable()->constrained('ai_generation_requests')->nullOnDelete();
                $table->boolean('submitted_for_admin_ai_review')->default(false);
                $table->timestamps();

                $table->index(['lecturer_id', 'status']);
                $table->index(['department_id', 'status']);
                $table->index(['course_id', 'topic']);
                $table->index('parent_question_id');
                $table->index(['submitted_for_admin_ai_review', 'generated_by_ai', 'status'], 'qb_admin_ai_review_idx');
            });
        }

        if (! Schema::hasTable('question_options')) {
            Schema::create('question_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_bank_id')->constrained('question_bank')->cascadeOnDelete();
                $table->char('option_key', 1);
                $table->text('option_text');
                $table->boolean('is_correct')->default(false);
                $table->timestamps();

                $table->unique(['question_bank_id', 'option_key']);
            });
        }

        if (! Schema::hasTable('question_versions')) {
            Schema::create('question_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_bank_id')->constrained('question_bank')->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
                $table->string('change_type', 40)->default('updated');
                $table->text('change_reason')->nullable();
                $table->json('snapshot');
                $table->timestamps();

                $table->index(['question_bank_id', 'version']);
            });
        }

        if (! Schema::hasTable('official_question_reviews')) {
            Schema::create('official_question_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_bank_id')->constrained('question_bank')->cascadeOnDelete();
                $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('review_note')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at']);
            });
        }

        if (! Schema::hasTable('question_usage_stats')) {
            Schema::create('question_usage_stats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_bank_id')->constrained('question_bank')->cascadeOnDelete();
                $table->unsignedInteger('times_used')->default(0);
                $table->decimal('average_score', 6, 2)->default(0);
                $table->decimal('correct_percentage', 6, 2)->default(0);
                $table->decimal('difficulty_performance', 6, 2)->default(0);
                $table->json('common_wrong_answers')->nullable();
                $table->decimal('ai_grading_consistency', 6, 2)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->unique('question_bank_id');
            });
        }

        if (! Schema::hasTable('question_audit_logs')) {
            Schema::create('question_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_bank_id')->nullable()->constrained('question_bank')->nullOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event_type', 80);
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['event_type', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('question_audit_logs');
        Schema::dropIfExists('question_usage_stats');
        Schema::dropIfExists('official_question_reviews');
        Schema::dropIfExists('question_versions');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('question_bank');
        Schema::dropIfExists('ai_generation_requests');
    }
};
