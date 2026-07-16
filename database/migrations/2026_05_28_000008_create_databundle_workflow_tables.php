<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_databundle_orders', function (Blueprint $table) {
            $table->id();
            $table->string('momo_name', 100)->nullable();
            $table->string('momo_number', 20)->nullable();
            $table->string('beneficiary_number', 20)->nullable();
            $table->string('package', 50);
            $table->string('package_mb', 50)->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->integer('megabytes')->default(0);
            $table->string('received', 50)->nullable();
            $table->string('network_type', 50);
            $table->string('external_transaction_id', 191)->nullable();
            $table->longText('api_response_data')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->index('reference');
            $table->index('network_type');
            $table->index('status');
        });

        Schema::create('verified_databundle_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_id')->nullable();
            $table->string('momo_name', 100)->nullable();
            $table->string('momo_number', 20)->nullable();
            $table->string('package', 50);
            $table->string('package_mb', 50)->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->integer('megabytes')->default(0);
            $table->string('received', 50)->nullable();
            $table->string('network_type', 50);
            $table->string('external_transaction_id', 191)->nullable();
            $table->longText('api_response_data')->nullable();
            $table->timestamp('payment_verified_at')->nullable();
            $table->timestamps();
            $table->index('reference');
            $table->index('network_type');
        });

        Schema::create('processing_databundle_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_id')->nullable();
            $table->unsignedBigInteger('verified_id')->nullable();
            $table->string('momo_name', 100)->nullable();
            $table->string('momo_number', 20)->nullable();
            $table->string('package', 50);
            $table->string('package_mb', 50)->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->integer('megabytes')->default(0);
            $table->string('received', 50)->nullable();
            $table->string('network_type', 50);
            $table->string('external_transaction_id', 191)->nullable();
            $table->longText('api_response_data')->nullable();
            $table->timestamp('payment_verified_at')->nullable();
            $table->timestamps();
            $table->index('reference');
            $table->index('network_type');
            $table->index('verified_id');
        });

        Schema::create('completed_databundle_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_id')->nullable();
            $table->unsignedBigInteger('verified_id')->nullable();
            $table->string('momo_name', 100)->nullable();
            $table->string('momo_number', 20)->nullable();
            $table->string('package', 50);
            $table->string('package_mb', 50)->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->integer('megabytes')->default(0);
            $table->string('received', 50)->nullable();
            $table->string('network_type', 50);
            $table->string('external_transaction_id', 191)->nullable();
            $table->longText('api_response_data')->nullable();
            $table->string('status', 20)->default('delivered');
            $table->timestamp('payment_verified_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index('reference');
            $table->index('network_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('completed_databundle_orders');
        Schema::dropIfExists('processing_databundle_orders');
        Schema::dropIfExists('verified_databundle_orders');
        Schema::dropIfExists('pending_databundle_orders');
    }
};
