<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_sellers', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'total_earnings']);
            $table->string('preferred_gateway', 50)->default('paystack')->after('balance');
        });

        Schema::table('sub_seller_balance_history', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->after('ref_id');
        });

        Schema::dropIfExists('sub_seller_earnings');
    }

    public function down(): void
    {
        Schema::table('sub_sellers', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(0)->after('ref_code');
            $table->decimal('total_earnings', 10, 2)->default(0)->after('commission_rate');
            $table->dropColumn('preferred_gateway');
        });

        Schema::table('sub_seller_balance_history', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::create('sub_seller_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('store_orders')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('type', 20)->default('commission');
            $table->string('status', 20)->default('pending');
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });
    }
};
