<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sub_agents')) {
            Schema::table('store_orders', function (Blueprint $table) {
                $table->dropForeign(['sub_seller_id']);
            });

            Schema::table('sub_seller_balance_history', function (Blueprint $table) {
                $table->dropForeign(['sub_seller_id']);
            });

            Schema::rename('sub_sellers', 'sub_agents');
            Schema::rename('sub_seller_balance_history', 'sub_agent_balance_history');

            Schema::table('store_orders', function (Blueprint $table) {
                $table->renameColumn('sub_seller_id', 'sub_agent_id');
                $table->renameColumn('sub_seller_profit', 'sub_agent_profit');
            });

            Schema::table('agent_package_pricing', function (Blueprint $table) {
                $table->renameColumn('sub_seller_price', 'sub_agent_price');
            });

            Schema::table('sub_agent_balance_history', function (Blueprint $table) {
                $table->renameColumn('sub_seller_id', 'sub_agent_id');
            });

            Schema::table('store_orders', function (Blueprint $table) {
                $table->foreign('sub_agent_id')->references('id')->on('sub_agents')->cascadeOnDelete();
            });

            Schema::table('sub_agent_balance_history', function (Blueprint $table) {
                $table->foreign('sub_agent_id')->references('id')->on('sub_agents')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropForeign(['sub_agent_id']);
        });

        Schema::table('sub_agent_balance_history', function (Blueprint $table) {
            $table->dropForeign(['sub_agent_id']);
        });

        Schema::table('store_orders', function (Blueprint $table) {
            $table->renameColumn('sub_agent_id', 'sub_seller_id');
            $table->renameColumn('sub_agent_profit', 'sub_seller_profit');
        });

        Schema::table('agent_package_pricing', function (Blueprint $table) {
            $table->renameColumn('sub_agent_price', 'sub_seller_price');
        });

        Schema::table('sub_agent_balance_history', function (Blueprint $table) {
            $table->renameColumn('sub_agent_id', 'sub_seller_id');
        });

        Schema::rename('sub_agent_balance_history', 'sub_seller_balance_history');
        Schema::rename('sub_agents', 'sub_sellers');

        Schema::table('store_orders', function (Blueprint $table) {
            $table->foreign('sub_seller_id')->references('id')->on('sub_sellers')->cascadeOnDelete();
        });

        Schema::table('sub_seller_balance_history', function (Blueprint $table) {
            $table->foreign('sub_seller_id')->references('id')->on('sub_sellers')->cascadeOnDelete();
        });
    }
};
