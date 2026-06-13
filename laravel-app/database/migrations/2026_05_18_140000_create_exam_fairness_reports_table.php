<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_fairness_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('fairness_score')->nullable();
            $table->string('overall_status', 80)->nullable();
            $table->unsignedSmallInteger('estimated_total_duration_minutes')->nullable();
            $table->string('duration_status', 80)->nullable();
            $table->json('difficulty_distribution')->nullable();
            $table->json('blooms_distribution')->nullable();
            $table->json('cognitive_difficulty_map')->nullable();
            $table->json('question_analysis')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('raw_ai_response')->nullable();
            $table->string('questions_fingerprint', 64)->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->unique('exam_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_fairness_reports');
    }
};
