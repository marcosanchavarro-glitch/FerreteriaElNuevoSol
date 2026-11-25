<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_a_role_and_audit_contains_no_credentials(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $hash = $user->password;
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee($user->email)->assertDontSee($hash);
        $this->patch(route('admin.users.update', $user), ['role' => 'seller', 'expected_role' => 'customer'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(UserRole::Seller, $user->fresh()->role);
        $this->assertSame($hash, $user->fresh()->password);
        $log = ActivityLog::where('module', 'users')->sole();
        $this->assertSame(['role' => 'customer'], $log->before_values);
        $this->assertSame(['role' => 'seller'], $log->after_values);
    }

    public function test_seller_and_customer_cannot_list_or_change_roles(): void
    {
        $target = User::factory()->create();
        foreach ([User::factory()->seller()->create(), User::factory()->create()] as $actor) {
            $this->actingAs($actor)->withSession(['password_hash_web' => $actor->password])->get(route('admin.users.index'))->assertForbidden();
            $this->patch(route('admin.users.update', $target), ['role' => 'admin', 'expected_role' => 'customer'])->assertForbidden();
        }
        $this->assertSame(UserRole::Customer, $target->fresh()->role);
    }

    public function test_admin_cannot_demote_self_or_overwrite_a_concurrent_role_change(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->seller()->create();
        $this->actingAs($admin)->patch(route('admin.users.update', $admin), ['role' => 'customer', 'expected_role' => 'admin'])->assertSessionHasErrors('role');
        $this->patch(route('admin.users.update', $user), ['role' => 'admin', 'expected_role' => 'customer'])->assertSessionHasErrors('role');
        $this->patch(route('admin.users.update', $user), ['role' => 'admin', 'expected_role' => 'seller', 'active' => false])->assertForbidden();
        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
        $this->assertSame(UserRole::Seller, $user->fresh()->role);
    }
}
