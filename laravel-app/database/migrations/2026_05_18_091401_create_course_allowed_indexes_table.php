<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_allowed_indexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('student_index', 50);
            $table->timestamps();
            $table->unique(['course_id', 'student_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_allowed_indexes');
    }
};
