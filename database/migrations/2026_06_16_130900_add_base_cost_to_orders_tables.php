<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('base_cost', 10, 2)->nullable()->after('amount')->comment('Actual wholesale cost from provider');
        });

        Schema::table('agent_orders', function (Blueprint $table) {
            $table->decimal('base_cost', 10, 2)->nullable()->after('amount')->comment('Actual wholesale cost from provider');
        });

        Schema::table('store_orders', function (Blueprint $table) {
            $table->decimal('base_cost', 10, 2)->nullable()->after('processing_fee')->comment('Actual wholesale cost from provider');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('base_cost');
        });

        Schema::table('agent_orders', function (Blueprint $table) {
            $table->dropColumn('base_cost');
        });

        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropColumn('base_cost');
        });
    }
};
