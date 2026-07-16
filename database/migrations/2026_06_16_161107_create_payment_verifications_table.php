<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 255)->unique();
            $table->string('gateway', 50)->default('paystack');
            $table->string('user_type', 50);
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['user_type', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_verifications');
    }
};
