<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Department;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_register_path_redirects_to_lecturer_register(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('lecturer.register', absolute: false));
    }

    public function test_lecturer_registration_screen_can_be_rendered(): void
    {
        $school = School::query()->create(['name' => 'Test School', 'code' => 'TS']);
        Department::query()->create([
            'school_id' => $school->id,
            'name' => 'Test Department',
            'code' => 'TD',
        ]);

        $response = $this->get('/lecturer/register');

        $response->assertStatus(200);
    }

    public function test_lecturer_registration_creates_pending_account(): void
    {
        $school = School::query()->create(['name' => 'Test School', 'code' => 'TS']);
        $department = Department::query()->create([
            'school_id' => $school->id,
            'name' => 'Test Department',
            'code' => 'TD',
        ]);

        $response = $this->post('/lecturer/register', [
            'name' => 'Jane Lecturer',
            'email' => 'jane@example.com',
            'staff_id' => 'STAFF-1001',
            'phone' => '+233000000000',
            'school_id' => $school->id,
            'department_id' => $department->id,
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertRedirect(route('login', absolute: false));
        $this->assertGuest();

        $user = User::query()->where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::Lecturer, $user->role);
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertTrue($user->is_active);
        $this->assertSame('STAFF-1001', $user->staff_id);
    }
}
