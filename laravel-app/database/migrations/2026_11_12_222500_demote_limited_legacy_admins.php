<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'super_admin')
            ->whereNotNull('permissions')
            ->update(['role' => 'user']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'user')
            ->whereNotNull('permissions')
            ->update(['role' => 'admin']);
    }
};
