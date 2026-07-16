<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_store_settings', function (Blueprint $table) {
            $table->string('preferred_gateway', 20)->default('paystack')->after('contact_whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('agent_store_settings', function (Blueprint $table) {
            $table->dropColumn('preferred_gateway');
        });
    }
};
