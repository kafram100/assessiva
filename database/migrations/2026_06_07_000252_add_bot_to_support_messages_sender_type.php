<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE support_messages MODIFY COLUMN sender_type ENUM('user', 'admin', 'bot') NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE support_messages MODIFY COLUMN sender_type ENUM('user', 'admin') NOT NULL DEFAULT 'user'");
    }
};
