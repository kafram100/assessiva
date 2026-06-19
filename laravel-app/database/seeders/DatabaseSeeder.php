<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Department;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo accounts for local testing. Password for all three: {@see self::TEST_PASSWORD}.
     */
    public const TEST_PASSWORD = 'password';

    public function run(): void
    {
        $school = School::query()->firstOrCreate(
            ['code' => 'DEMO'],
            [
                'name' => 'Demo School of Engineering',
                'description' => 'Seeded for local development and QA logins.',
            ],
        );

        $department = Department::query()->firstOrCreate(
            ['school_id' => $school->id, 'code' => 'CS'],
            [
                'name' => 'Computer Science',
                'description' => 'Demo department for lecturer / HOD test users.',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username' => 'admin',
                'name' => 'Administrator',
                'password' => Hash::make(self::TEST_PASSWORD),
                'role' => UserRole::Admin,
                'status' => UserStatus::Approved,
                'department_id' => null,
                'school_id' => null,
                'staff_id' => null,
                'is_active' => true,
                'approved_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'lecturer@example.com'],
            [
                'username' => 'lecturer',
                'name' => 'Demo Lecturer',
                'password' => Hash::make(self::TEST_PASSWORD),
                'role' => UserRole::Hod,
                'status' => UserStatus::Approved,
                'department_id' => $department->id,
                'school_id' => $school->id,
                'staff_id' => 'LEC-DEMO-001',
                'phone' => '+10000000001',
                'is_active' => true,
                'approved_at' => now(),
            ],
        );

    }
}
