<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('questions')) {
            return;
        }

        if (Schema::hasColumn('questions', 'question_bank_id')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedBigInteger('question_bank_id')->nullable()->after('exam_id');
            $table->index('question_bank_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('questions') || ! Schema::hasColumn('questions', 'question_bank_id')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['question_bank_id']);
            $table->dropColumn('question_bank_id');
        });
    }
};
