<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_config', function (Blueprint $table) {
            $table->id();
            $table->string('config_key', 100)->unique();
            $table->text('config_value');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('api_config', function (Blueprint $table) {
            $table->id();
            $table->string('network_type', 50);
            $table->string('api_name', 100)->nullable();
            $table->string('api_endpoint', 500);
            $table->string('api_key', 255)->nullable();
            $table->string('api_key_id', 100)->nullable();
            $table->string('api_secret', 255)->nullable();
            $table->string('request_method', 10)->default('POST');
            $table->text('request_headers')->nullable();
            $table->text('request_body_template');
            $table->string('response_success_field', 100)->default('success');
            $table->string('response_data_field', 100)->nullable();
            $table->string('response_error_field', 100)->nullable();
            $table->integer('timeout_seconds')->default(30);
            $table->integer('retry_attempts')->default(3);
            $table->string('status_endpoint', 500)->nullable();
            $table->string('balance_endpoint', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('network_type');
            $table->index('is_active');
        });

        Schema::create('orders_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method', 50)->default('wallet');
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders_payments');
        Schema::dropIfExists('api_config');
        Schema::dropIfExists('payment_config');
    }
};
