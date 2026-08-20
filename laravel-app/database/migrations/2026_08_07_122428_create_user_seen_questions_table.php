<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_seen_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('question_bank')->cascadeOnDelete();
            $table->unsignedInteger('seen_count')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            // Each (user, question) pair must be unique
            $table->unique(['user_id', 'question_id']);

            // Fast lookup: find least-recently-seen questions for a user
            $table->index(['user_id', 'last_seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_seen_questions');
    }
};
