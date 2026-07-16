<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('message');
            $table->string('type', 20)->default('info');
            $table->string('priority', 20)->default('normal');
            $table->string('recipient_type', 20)->default('all');
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            $table->index('recipient_id');
            $table->index('sender_id');
            $table->index('is_read');
        });

        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 120)->unique();
            $table->string('account_type', 32)->default('agent');
            $table->unsignedBigInteger('account_id');
            $table->string('email', 191)->nullable();
            $table->decimal('base_amount', 10, 2)->default(0);
            $table->decimal('processing_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('provider', 50)->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('credited_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index(['account_type', 'account_id']);
        });

        Schema::create('api_submission_registry', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('agent_order_id')->nullable();
            $table->string('network_type', 50);
            $table->string('reference', 100)->nullable();
            $table->string('external_transaction_id', 100)->nullable();
            $table->text('api_response')->nullable();
            $table->string('status', 50)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index('order_id');
            $table->index('agent_order_id');
            $table->index('network_type');
            $table->index('reference');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_submission_registry');
        Schema::dropIfExists('wallet_topups');
        Schema::dropIfExists('notifications');
    }
};
