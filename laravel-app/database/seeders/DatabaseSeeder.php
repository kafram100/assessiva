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
        // Super Admin (ID 1 gets all permissions in hasPermission())
        User::query()->updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'username' => 'superadmin',
                'name' => 'Super Admin',
                'password' => Hash::make(self::TEST_PASSWORD),
                'role' => UserRole::Admin,
                'status' => UserStatus::Approved,
                'is_active' => true,
                'approved_at' => now(),
            ],
        );

        // Support Admin (with granular permissions)
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username' => 'admin',
                'name' => 'Support Admin',
                'password' => Hash::make(self::TEST_PASSWORD),
                'role' => UserRole::Admin,
                'status' => UserStatus::Approved,
                'permissions' => ['load_questions', 'delete_questions', 'edit_questions'],
                'is_active' => true,
                'approved_at' => now(),
            ],
        );

        // Regular User (student)
        User::query()->updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'username' => 'user',
                'name' => 'Demo User',
                'password' => Hash::make(self::TEST_PASSWORD),
                'role' => UserRole::Student,
                'status' => UserStatus::Approved,
                'is_active' => true,
                'approved_at' => now(),
            ],
        );
    }
}
