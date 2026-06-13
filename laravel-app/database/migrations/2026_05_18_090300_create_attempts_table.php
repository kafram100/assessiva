<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attempts')) {
            Schema::create('attempts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exam_id');
                $table->string('student_index', 50);
                $table->string('student_fullname', 150)->nullable();
                $table->string('student_level', 50)->nullable();
                $table->string('student_email', 150)->nullable();
                $table->timestamp('start_time')->useCurrent();
                $table->timestamp('submit_time')->nullable();
                $table->decimal('score', 10, 2)->default(0);
                $table->integer('total_marks')->default(0);
                $table->enum('status', ['ongoing', 'completed'])->default('ongoing');
                $table->text('notes')->nullable();
                $table->string('locked_by_session', 64)->nullable();
                $table->text('question_order')->nullable();
                $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
