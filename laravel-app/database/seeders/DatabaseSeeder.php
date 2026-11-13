<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public const TEST_PASSWORD = 'password';

    public function run(): void
    {
        $superadminPassword = env('SUPERADMIN_PASSWORD', self::TEST_PASSWORD);

        // The configured Super Admin is the only account created with elevated access.
        User::query()->updateOrCreate(
            ['email' => env('SUPERADMIN_EMAIL', 'superadmin@example.com')],
            [
                'username' => 'superadmin',
                'name' => 'Super Admin',
                'password' => Hash::make($superadminPassword),
                'role' => UserRole::SuperAdmin,
                'status' => UserStatus::Approved,
                'is_active' => true,
                'approved_at' => now(),
            ],
        );

        // Regular User
        User::query()->updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'username' => 'user',
                'name' => 'Demo User',
                'password' => Hash::make(self::TEST_PASSWORD),
                'role' => UserRole::User,
                'status' => UserStatus::Approved,
                'is_active' => true,
                'approved_at' => now(),
            ],
        );
    }
}
