<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_activity_logs')) {
            Schema::create('exam_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exam_attempt_id');
                $table->integer('user_id')->default(0);
                $table->string('activity_type', 50);
                $table->text('description')->nullable();
                $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
                $table->timestamp('timestamp')->useCurrent();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('additional_data')->nullable();
                $table->boolean('reviewed')->default(false);
                $table->integer('reviewed_by')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
                $table->index('exam_attempt_id');
                $table->index('activity_type');
                $table->index('severity');
            });
        }

        if (! Schema::hasTable('exam_reports')) {
            Schema::create('exam_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exam_id');
                $table->unsignedBigInteger('lecturer_id');
                $table->timestamp('report_date')->useCurrent();
                $table->integer('total_attempts')->default(0);
                $table->integer('flagged_attempts')->default(0);
                $table->integer('high_risk_attempts')->default(0);
                $table->decimal('average_severity', 3, 2)->default(0.00);
                $table->text('summary_report')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
                $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
                $table->foreign('lecturer_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index('exam_id');
            });
        }

        if (! Schema::hasTable('cheating_incidents')) {
            Schema::create('cheating_incidents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exam_attempt_id');
                $table->integer('user_id')->default(0);
                $table->string('incident_type', 50);
                $table->string('evidence_path', 255)->nullable();
                $table->enum('confidence_level', ['low', 'medium', 'high'])->default('medium');
                $table->enum('status', ['pending', 'reviewed', 'confirmed', 'dismissed'])->default('pending');
                $table->timestamp('incident_timestamp')->useCurrent();
                $table->text('notes')->nullable();
                $table->integer('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
                $table->index('exam_attempt_id');
                $table->index('incident_type');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cheating_incidents');
        Schema::dropIfExists('exam_reports');
        Schema::dropIfExists('exam_activity_logs');
    }
};
