<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_store_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->string('store_name', 100)->nullable();
            $table->string('store_slug', 50)->unique()->nullable();
            $table->text('store_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('theme_color', 7)->default('#3B82F6');
            $table->string('logo_url', 255)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_whatsapp', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('admin_store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 50)->unique();
            $table->text('setting_value')->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('sub_sellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->string('username', 50)->unique();
            $table->string('password_hash', 255);
            $table->string('full_name', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('ref_code', 20)->unique()->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_ref', 20)->unique()->nullable();
            $table->string('tracking_id', 10)->unique()->nullable();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sub_seller_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('customer_phone', 20);
            $table->string('customer_name', 100)->nullable();
            $table->string('customer_email', 100)->nullable();
            $table->unsignedBigInteger('package_id');
            $table->string('network_type', 20)->nullable();
            $table->decimal('package_size_gb', 10, 2)->nullable();
            $table->decimal('base_price', 10, 2);
            $table->decimal('sold_price', 10, 2);
            $table->decimal('agent_profit', 10, 2)->default(0);
            $table->decimal('sub_seller_profit', 10, 2)->default(0);
            $table->decimal('your_profit', 10, 2)->default(0);
            $table->string('payment_method', 50)->default('paystack');
            $table->string('payment_reference', 100)->nullable();
            $table->string('status', 50)->default('pending');
            $table->string('failure_code', 100)->nullable();
            $table->string('visibility_scope', 20)->default('all');
            $table->string('payment_status', 50)->default('pending');
            $table->unsignedBigInteger('linked_agent_order_id')->nullable();
            $table->string('external_transaction_id', 100)->nullable();
            $table->string('external_reference', 100)->nullable();
            $table->text('api_response_data')->nullable();
            $table->text('api_response')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('payment_status');
            $table->index('linked_agent_order_id');
            $table->index('visibility_scope');
        });

        Schema::create('store_order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('store_orders')->cascadeOnDelete();
            $table->string('status', 50);
            $table->text('notes')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
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

        Schema::create('sub_seller_balance_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_seller_id')->constrained()->cascadeOnDelete();
            $table->decimal('change_amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->string('reason', 50)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_package_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('agent_packages')->cascadeOnDelete();
            $table->decimal('customer_price', 10, 2);
            $table->decimal('sub_seller_price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['agent_id', 'package_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_package_pricing');
        Schema::dropIfExists('sub_seller_balance_history');
        Schema::dropIfExists('sub_seller_earnings');
        Schema::dropIfExists('store_order_status_history');
        Schema::dropIfExists('store_orders');
        Schema::dropIfExists('sub_sellers');
        Schema::dropIfExists('admin_store_settings');
        Schema::dropIfExists('agent_store_settings');
    }
};
