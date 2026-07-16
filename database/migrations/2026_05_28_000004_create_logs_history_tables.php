<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->string('network_type', 50);
            $table->string('package_size', 50);
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('network_type');
            $table->index('is_active');
        });

        Schema::create('balance_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('change_amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->string('reason', 30);
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('beneficiary_number', 15)->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();
            $table->index('agent_id');
        });

        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('agent_order_id')->nullable();
            $table->string('network_type', 50);
            $table->string('api_endpoint', 500);
            $table->text('request_data')->nullable();
            $table->text('response_data')->nullable();
            $table->integer('response_code')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->timestamps();
            $table->index('order_id');
            $table->index('agent_order_id');
            $table->index('network_type');
            $table->index('success');
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('external_transaction_id', 100)->nullable();
            $table->string('webhook_type', 50);
            $table->text('payload');
            $table->string('response_status', 50)->nullable();
            $table->boolean('processed')->default(false);
            $table->timestamps();
            $table->index('order_id');
            $table->index('external_transaction_id');
            $table->index('webhook_type');
            $table->index('processed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('api_logs');
        Schema::dropIfExists('balance_history');
        Schema::dropIfExists('agent_pricing_rules');
    }
};
