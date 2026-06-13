<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 100);
            $table->string('course_name', 100);
            $table->string('course_code', 50)->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('duration');
            $table->unsignedInteger('attempts_allowed')->default(1);
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('end_datetime')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('results_released')->default(false);
            $table->string('exam_code', 20)->unique();
            $table->string('exam_type', 50)->nullable();
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('submission_password_enabled')->default(false);
            $table->string('submission_password')->nullable();
            $table->string('assessment_file', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
