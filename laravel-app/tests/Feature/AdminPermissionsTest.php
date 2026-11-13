<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admins_and_admins_can_access_the_admin_dashboard(): void
    {
        $user = $this->createAccount(UserRole::User);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $superAdmin = $this->createAccount(UserRole::SuperAdmin);

        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.is_super_admin', true)
                ->where('auth.admin_permissions', ['*']));

        $admin = $this->createAccount(UserRole::Admin, ['manage_questions']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.is_super_admin', false)
                ->where('auth.is_admin', true)
                ->where('auth.admin_permissions', ['manage_questions']));
    }

    public function test_role_protected_question_bank_is_available_to_super_admins_and_permitted_admins(): void
    {
        $user = $this->createAccount(UserRole::User);

        $this->actingAs($user)
            ->get(route('admin.question-bank-catalog.index'))
            ->assertForbidden();

        $adminWithoutPermission = $this->createAccount(UserRole::Admin, ['view_reports']);

        $this->actingAs($adminWithoutPermission)
            ->get(route('admin.question-bank-catalog.index'))
            ->assertForbidden();

        $adminWithPermission = $this->createAccount(UserRole::Admin, ['manage_questions']);

        $this->actingAs($adminWithPermission)
            ->get(route('admin.question-bank-catalog.index'))
            ->assertOk();

        $superAdmin = $this->createAccount(UserRole::SuperAdmin);

        $this->actingAs($superAdmin)
            ->get(route('admin.question-bank-catalog.index'))
            ->assertOk();
    }

    public function test_profile_page_is_available_for_all_roles(): void
    {
        foreach ([UserRole::User, UserRole::Admin, UserRole::SuperAdmin] as $role) {
            $user = $this->createAccount($role);

            $this->actingAs($user)
                ->get(route('profile.edit'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component('Profile/Edit'));
        }
    }

    public function test_super_admin_is_resolved_by_role_not_user_id_or_permissions(): void
    {
        $user = $this->createAccount(UserRole::User, ['*']);
        $admin = $this->createAccount(UserRole::Admin, ['manage_billing']);
        $superAdmin = $this->createAccount(UserRole::SuperAdmin);

        $this->assertNotSame(1, $superAdmin->id);
        $this->assertFalse($user->isSuperAdmin());
        $this->assertFalse($user->hasPermission('manage_billing'));
        $this->assertFalse($admin->isSuperAdmin());
        $this->assertTrue($admin->hasPermission('manage_billing'));
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->hasPermission('manage_billing'));
    }

    public function test_only_super_admins_can_access_admin_management_routes(): void
    {
        $user = $this->createAccount(UserRole::User);
        $admin = $this->createAccount(UserRole::Admin, ['manage_users']);
        $superAdmin = $this->createAccount(UserRole::SuperAdmin);

        $this->actingAs($user)
            ->get('/admin/admins')
            ->assertForbidden();

        $this->actingAs($admin)
            ->get('/admin/admins')
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->get('/admin/admins')
            ->assertOk();
    }

    public function test_super_admin_can_create_admin_account_with_permissions(): void
    {
        $superAdmin = $this->createAccount(UserRole::SuperAdmin);

        $response = $this->actingAs($superAdmin)
            ->post('/admin/admins', [
                'name' => 'Sub Admin',
                'username' => 'subadmin',
                'email' => 'subadmin@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'permissions' => ['manage_questions', 'manage_users'],
            ]);

        $response->assertRedirect('/admin/admins');

        $this->assertDatabaseHas('users', [
            'username' => 'subadmin',
            'email' => 'subadmin@example.com',
            'role' => UserRole::Admin->value,
        ]);

        $created = User::where('username', 'subadmin')->first();
        $this->assertTrue($created->hasPermission('manage_questions'));
        $this->assertTrue($created->hasPermission('manage_users'));
        $this->assertFalse($created->hasPermission('manage_billing'));
    }

    public function test_user_management_cannot_delete_super_admin_accounts(): void
    {
        $user = $this->createAccount(UserRole::User);
        $superAdmin = $this->createAccount(UserRole::SuperAdmin);

        $this->actingAs($superAdmin)
            ->delete(route('admin.students.delete'), ['ids' => [$superAdmin->id]])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    private function createAccount(UserRole $role, array $permissions = []): User
    {
        static $counter = 0;
        $counter++;

        return User::create([
            'name' => 'Account '.$counter,
            'username' => 'account'.$counter,
            'email' => 'account'.$counter.'@example.com',
            'password' => 'password',
            'role' => $role,
            'status' => UserStatus::Approved,
            'is_active' => true,
            'permissions' => $permissions,
        ]);
    }
}
