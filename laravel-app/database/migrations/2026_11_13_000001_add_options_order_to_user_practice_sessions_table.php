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
        if (Schema::hasTable('user_practice_sessions') && ! Schema::hasColumn('user_practice_sessions', 'options_order')) {
            Schema::table('user_practice_sessions', function (Blueprint $table): void {
                $table->json('options_order')->nullable()->after('questions');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_practice_sessions') && Schema::hasColumn('user_practice_sessions', 'options_order')) {
            Schema::table('user_practice_sessions', function (Blueprint $table): void {
                $table->dropColumn('options_order');
            });
        }
    }
};
