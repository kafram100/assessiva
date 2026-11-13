<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public const DEFAULT_PASSWORD = 'Password123!';

    public function run(): void
    {
        $superadminPassword = env('SUPERADMIN_PASSWORD', self::DEFAULT_PASSWORD);

        // 1. Primary Administrator
        User::query()->updateOrCreate(
            ['email' => 'trainovaedutech@gmail.com'],
            [
                'username' => 'Novagrade',
                'name' => 'Novagrade',
                'password' => Hash::make($superadminPassword),
                'role' => UserRole::SuperAdmin,
                'status' => UserStatus::Approved,
                'is_active' => true,
                'approved_at' => now(),
            ],
        );

        // 2. Default System Super Admin
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

        // 3. Demo Regular User
        User::query()->updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'username' => 'user',
                'name' => 'Demo User',
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'role' => UserRole::User,
                'status' => UserStatus::Approved,
                'is_active' => true,
                'approved_at' => now(),
            ],
        );
    }
}
