<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_packages', function (Blueprint $table) {
            $table->integer('voice_minutes')->nullable()->after('package_size_mb');
        });
    }

    public function down(): void
    {
        Schema::table('agent_packages', function (Blueprint $table) {
            $table->dropColumn('voice_minutes');
        });
    }
};