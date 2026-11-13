<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BillingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_settings_never_send_the_paystack_secret_to_the_browser(): void
    {
        AppSetting::setValue('paystack_secret_key', 'sk_test_private');

        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]))
            ->get(route('admin.billing-settings'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/BillingSettings')
                ->where('settings.paystack_secret_configured', true)
                ->missing('settings.paystack_secret_key')
                ->missing('settings.moolre_secret_key'));
    }

    public function test_saving_billing_settings_with_a_blank_secret_preserves_the_saved_key(): void
    {
        AppSetting::setValue('paystack_secret_key', 'sk_test_private');

        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]))
            ->post(route('admin.billing-settings.update'), [
                'active_payment_gateway' => 'paystack',
                'token_price_per_question' => '1.50',
                'paystack_public_key' => 'pk_test_public',
                'paystack_secret_key' => '',
            ])
            ->assertRedirect(route('admin.billing-settings'));

        $this->assertSame('sk_test_private', AppSetting::getValue('paystack_secret_key'));
        $this->assertSame('pk_test_public', AppSetting::getValue('paystack_public_key'));
    }

    public function test_moolre_cannot_be_selected_while_integration_is_deferred(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]))
            ->post(route('admin.billing-settings.update'), [
                'active_payment_gateway' => 'moolre',
                'token_price_per_question' => '1',
            ])
            ->assertSessionHasErrors('active_payment_gateway');
    }

    public function test_existing_moolre_setting_cannot_start_a_mock_payment(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        AppSetting::setValue('active_payment_gateway', 'moolre');

        $this->actingAs($user)
            ->postJson(route('student.wallet.topup.initialize'), ['amount' => 10])
            ->assertStatus(400)
            ->assertJsonFragment(['error' => 'Moolre payments are disabled until the live integration is configured. Select Paystack.']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_paystack_cannot_be_activated_without_a_secret_key(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]))
            ->post(route('admin.billing-settings.update'), [
                'active_payment_gateway' => 'paystack',
                'token_price_per_question' => '1',
                'paystack_secret_key' => '',
            ])
            ->assertSessionHasErrors('paystack_secret_key');
    }

    public function test_pending_paystack_callback_uses_the_transaction_gateway_after_switch(): void
    {
        $user = User::factory()->create(['role' => UserRole::User, 'tokens' => 0]);
        AppSetting::setValue('active_payment_gateway', 'moolre');
        AppSetting::setValue('paystack_secret_key', 'sk_test_private');
        AppSetting::setValue('token_price_per_question', '1');

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'reference' => 'paystack-reference',
            'amount' => 10,
            'tokens' => 0,
            'gateway' => 'paystack',
            'status' => 'pending',
        ]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'amount' => 1020],
            ]),
        ]);

        $this->actingAs($user)
            ->get(route('student.wallet.topup.callback', ['reference' => $transaction->reference]))
            ->assertRedirect(route('student.wallet'));

        $this->assertSame(10, $user->fresh()->tokens);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'gateway' => 'paystack',
            'status' => 'success',
            'tokens' => 10,
        ]);
    }

    public function test_callback_cannot_verify_another_users_transaction(): void
    {
        $owner = User::factory()->create(['role' => UserRole::User, 'tokens' => 0]);
        $otherUser = User::factory()->create(['role' => UserRole::User, 'tokens' => 0]);
        $transaction = Transaction::create([
            'user_id' => $owner->id,
            'reference' => 'private-reference',
            'amount' => 10,
            'tokens' => 0,
            'gateway' => 'paystack',
            'status' => 'pending',
        ]);

        Http::fake();

        $this->actingAs($otherUser)
            ->get(route('student.wallet.topup.callback', ['reference' => $transaction->reference]))
            ->assertRedirect(route('student.wallet'))
            ->assertSessionHas('error', 'Transaction record not found.');

        $this->assertSame(0, $owner->fresh()->tokens);
        $this->assertSame(0, $otherUser->fresh()->tokens);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'pending',
        ]);
        Http::assertNothingSent();
    }
}
