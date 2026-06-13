<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_sessions_proctoring')) {
            Schema::create('exam_sessions_proctoring', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exam_attempt_id');
                $table->integer('student_id')->default(0);
                $table->integer('lecturer_id')->default(0);
                $table->string('video_recording_path', 255)->nullable();
                $table->string('audio_recording_path', 255)->nullable();
                $table->timestamp('start_time')->useCurrent();
                $table->timestamp('end_time')->nullable();
                $table->integer('suspicious_activity_count')->default(0);
                $table->enum('proctoring_status', ['active', 'completed', 'flagged', 'violated'])->default('active');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
                $table->index('exam_attempt_id');
            });
        }

        if (! Schema::hasTable('exam_security_logs')) {
            Schema::create('exam_security_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exam_attempt_id');
                $table->integer('user_id')->default(0);
                $table->string('activity_type', 100);
                $table->text('description');
                $table->timestamp('timestamp');
                $table->string('ip_address', 45)->nullable();
                $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
                $table->boolean('reviewed')->default(false);
                $table->timestamp('created_at')->useCurrent();
                $table->index('exam_attempt_id');
                $table->index('activity_type');
                $table->index('timestamp');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_security_logs');
        Schema::dropIfExists('exam_sessions_proctoring');
    }
};
