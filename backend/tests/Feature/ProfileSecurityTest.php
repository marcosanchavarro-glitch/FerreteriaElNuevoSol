<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function profile(User $user): array
    {
        return ['first_name' => 'Nuevo', 'last_name' => 'Apellido', 'phone' => '123456', 'email' => $user->email];
    }

    public function test_user_can_view_and_update_own_profile(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/mi-cuenta')->assertOk()->assertSee($user->email)->assertDontSee($user->password);
        $this->patch('/mi-cuenta', $this->profile($user))->assertSessionHasNoErrors();
        $this->assertSame('Nuevo', $user->fresh()->first_name);
        $this->assertSame('123456', $user->fresh()->phone);
    }

    public static function protectedFields(): array
    {
        return [['id'], ['user_id'], ['role'], ['active'], ['email_verified_at'], ['password'], ['remember_token']];
    }

    #[DataProvider('protectedFields')]
    public function test_profile_rejects_identity_or_privilege_tampering(string $field): void
    {
        $user = User::factory()->seller()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->patch('/mi-cuenta', [...$this->profile($user), $field => $field === 'role' ? 'admin' : $other->id])->assertForbidden();
        $this->assertNotSame('Nuevo', $other->fresh()->first_name);
        $this->assertSame('seller', $user->fresh()->role->value);
    }

    public function test_other_user_profile_url_does_not_exist(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->patch('/mi-cuenta/'.$other->id, $this->profile($other))->assertNotFound();
    }

    public function test_email_change_requires_password_resets_verification_and_invalidates_reset_token(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Password::createToken($user);
        $data = [...$this->profile($user), 'email' => 'changed@example.test'];
        $this->actingAs($user)->patch('/mi-cuenta', $data)->assertSessionHasErrors('current_password');
        $this->patch('/mi-cuenta', [...$data, 'current_password' => 'SoloDesarrollo!2026'])->assertSessionHasNoErrors();
        $this->assertSame('changed@example.test', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_email_cannot_collide_with_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->patch('/mi-cuenta', [...$this->profile($user), 'email' => $other->email, 'current_password' => 'SoloDesarrollo!2026'])->assertSessionHasErrors('email');
    }

    public function test_change_password_requires_current_password_and_keeps_current_session(): void
    {
        $user = User::factory()->create();
        $data = ['current_password' => 'Wrong2026!', 'password' => 'Replacement2026!', 'password_confirmation' => 'Replacement2026!'];
        $this->actingAs($user)->put('/mi-cuenta/contrasena', $data)->assertSessionHasErrors('current_password')->assertSessionMissing('_old_input.current_password');
        $this->put('/mi-cuenta/contrasena', [...$data, 'current_password' => 'SoloDesarrollo!2026'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Replacement2026!', $user->fresh()->password));
        $this->get('/mi-cuenta')->assertOk();
    }

    public function test_password_change_rejects_other_user_id(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->put('/mi-cuenta/contrasena', ['user_id' => $other->id, 'current_password' => 'SoloDesarrollo!2026', 'password' => 'Replacement2026!', 'password_confirmation' => 'Replacement2026!'])->assertForbidden();
        $this->assertTrue(Hash::check('SoloDesarrollo!2026', $other->fresh()->password));
    }
}
