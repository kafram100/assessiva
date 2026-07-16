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
        if (!Schema::hasColumn('agent_packages', 'manual_fulfillment')) {
            Schema::table('agent_packages', function (Blueprint $table) {
                $table->boolean('manual_fulfillment')->default(false)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::table('agent_packages', function (Blueprint $table) {
            $table->dropColumn('manual_fulfillment');
        });
    }
};
