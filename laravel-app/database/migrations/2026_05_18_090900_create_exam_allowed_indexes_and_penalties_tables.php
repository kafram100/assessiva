<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_allowed_indexes')) {
            Schema::create('exam_allowed_indexes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exam_id');
                $table->string('student_index', 50);
                $table->timestamps();
                $table->index('exam_id');
                $table->unique(['exam_id', 'student_index']);
            });
        }

        if (! Schema::hasTable('exam_cheating_penalties')) {
            Schema::create('exam_cheating_penalties', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exam_id');
                $table->string('event_type', 80);
                $table->boolean('is_enabled')->default(false);
                $table->unsignedInteger('deduction_points')->default(0);
                $table->timestamps();
                $table->index('exam_id');
                $table->unique(['exam_id', 'event_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_cheating_penalties');
        Schema::dropIfExists('exam_allowed_indexes');
    }
};
