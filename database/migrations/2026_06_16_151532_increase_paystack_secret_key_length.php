<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE agent_store_settings MODIFY paystack_secret_key TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE agent_store_settings MODIFY paystack_secret_key VARCHAR(255) NULL');
    }
};
