<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('package_size', 50);
            $table->decimal('amount', 10, 2);
            $table->string('beneficiary_number', 20);
            $table->string('network_type', 50);
            $table->string('status', 20)->default('pending');
            $table->string('order_reference', 100)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('external_transaction_id', 100)->nullable();
            $table->string('external_reference', 100)->nullable();
            $table->text('api_response_data')->nullable();
            $table->string('momo_name', 100)->nullable();
            $table->string('momo_number', 20)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('tracking_key', 20)->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('network_type');
            $table->index('order_reference');
            $table->index('tracking_key');
        });

        Schema::create('agent_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->string('package_size', 50);
            $table->decimal('amount', 10, 2);
            $table->string('beneficiary_number', 20);
            $table->string('network_type', 50);
            $table->string('status', 20)->default('pending');
            $table->string('external_transaction_id', 100)->nullable();
            $table->string('external_reference', 100)->nullable();
            $table->text('api_response_data')->nullable();
            $table->string('momo_name', 100)->nullable();
            $table->string('momo_number', 20)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('tracking_key', 20)->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('network_type');
            $table->index('tracking_key');
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('old_status', 50)->nullable();
            $table->string('new_status', 50);
            $table->text('change_reason')->nullable();
            $table->string('changed_by', 50)->default('system');
            $table->text('external_data')->nullable();
            $table->timestamps();
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('agent_orders');
        Schema::dropIfExists('orders');
    }
};
