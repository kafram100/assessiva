<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('answers')) {
            Schema::create('answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('attempt_id');
                $table->unsignedBigInteger('question_id');
                $table->char('selected_option', 1)->nullable();
                $table->text('theory_answer')->nullable();
                $table->string('file_upload', 255)->nullable();
                $table->boolean('is_correct')->nullable();
                $table->integer('marks_awarded')->nullable();
                $table->foreign('attempt_id')->references('id')->on('attempts')->cascadeOnDelete();
                $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
