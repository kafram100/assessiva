<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'proctoring_mode')) {
                $table->string('proctoring_mode', 20)->default('auto')->after('exam_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'proctoring_mode')) {
                $table->dropColumn('proctoring_mode');
            }
        });
    }
};
