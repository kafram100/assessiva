<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agent_store_settings', function (Blueprint $table) {
            $table->string('paystack_public_key', 255)->nullable()->after('preferred_gateway');
            $table->string('paystack_secret_key', 255)->nullable()->after('paystack_public_key');
        });
    }

    public function down(): void
    {
        Schema::table('agent_store_settings', function (Blueprint $table) {
            $table->dropColumn(['paystack_public_key', 'paystack_secret_key']);
        });
    }
};
