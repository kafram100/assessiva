<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            if (! Schema::hasColumn('attempts', 'exam_device_key')) {
                $table->string('exam_device_key', 64)->nullable()->after('locked_by_session');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            if (Schema::hasColumn('attempts', 'exam_device_key')) {
                $table->dropColumn('exam_device_key');
            }
        });
    }
};
