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
        if (!Schema::hasColumn('agent_orders', 'package_id')) {
            Schema::table('agent_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('package_id')->nullable()->after('agent_id');
                $table->foreign('package_id')->references('id')->on('agent_packages')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('agent_orders', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn('package_id');
        });
    }
};
