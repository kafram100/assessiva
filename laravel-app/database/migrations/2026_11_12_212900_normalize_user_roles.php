<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'admin')
            ->whereNull('permissions')
            ->update(['role' => 'super_admin']);

        DB::table('users')
            ->where(function ($query): void {
                $query->where('role', '!=', 'super_admin')
                    ->orWhereNull('role');
            })
            ->update(['role' => 'user']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'super_admin')
            ->update(['role' => 'admin']);

        DB::table('users')
            ->where('role', 'user')
            ->update(['role' => 'student']);
    }
};
