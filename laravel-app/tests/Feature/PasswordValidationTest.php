<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class PasswordValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_password_rule_matches_live_strength_requirements(): void
    {
        $weakPasswords = [
            'S1!a',
            'lowercase1!',
            'UPPERCASE1!',
            'NoNumber!!',
            'NoSymbol1',
        ];

        foreach ($weakPasswords as $password) {
            $validator = Validator::make(
                ['password' => $password],
                ['password' => ['required', Password::defaults()]],
            );

            $this->assertFalse($validator->passes(), "Password unexpectedly accepted: {$password}");
        }

        $validator = Validator::make(
            ['password' => 'ValidPass1!'],
            ['password' => ['required', Password::defaults()]],
        );

        $this->assertTrue($validator->passes());
    }

    public function test_profile_password_update_does_not_require_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CurrentPass1!'),
        ]);

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'CurrentPass1!',
                'password' => 'UpdatedPass1!',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('UpdatedPass1!', $user->refresh()->password));
    }
}
