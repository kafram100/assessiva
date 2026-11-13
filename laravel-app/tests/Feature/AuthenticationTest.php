<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Novagrade',
            'username' => 'Novagrade',
            'email' => 'trainovaedutech@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => UserRole::SuperAdmin,
            'status' => UserStatus::Approved,
            'is_active' => true,
        ]);
    }

    public function test_login_fails_with_specific_message_when_account_does_not_exist(): void
    {
        $response = $this->post(route('login'), [
            'login' => 'nonexistent@example.com',
            'password' => 'SomePassword123!',
        ]);

        $response->assertSessionHasErrors([
            'login' => 'No account was found with that email or username. Please check your spelling.',
        ]);
        $this->assertGuest();
    }

    public function test_login_fails_with_specific_message_when_password_is_incorrect(): void
    {
        $admin = $this->createAdmin();

        $response = $this->post(route('login'), [
            'login' => $admin->email,
            'password' => 'WrongPassword123!',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'The password you entered is incorrect. Please try again.',
        ]);
        $this->assertGuest();
    }

    public function test_login_succeeds_with_email_and_correct_password(): void
    {
        $admin = $this->createAdmin();

        $response = $this->post(route('login'), [
            'login' => 'trainovaedutech@gmail.com',
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_login_succeeds_with_case_insensitive_username_and_whitespace(): void
    {
        $admin = $this->createAdmin();

        $response = $this->post(route('login'), [
            'login' => ' novagrade ', // lowercase and surrounded with spaces
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard'));
    }
}
