<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public static function roles(): array
    {
        return [['admin'], ['seller'], ['customer']];
    }

    #[DataProvider('roles')]
    public function test_registration_always_creates_an_active_customer(string $role): void
    {
        $this->post('/registro', ['first_name' => 'Ana', 'last_name' => 'Prueba', 'email' => ' ANA@example.test ', 'password' => 'Password2026!', 'password_confirmation' => 'Password2026!', 'role' => $role, 'active' => false, 'email_verified_at' => now(), 'id' => 999])->assertRedirect('/mi-cuenta');
        $user = User::sole();
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertTrue($user->active);
        $this->assertNull($user->email_verified_at);
        $this->assertNotSame(999, $user->id);
        $this->assertSame('ana@example.test', $user->email);
        $this->assertTrue(Hash::check('Password2026!', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_rejects_duplicate_email_and_weak_password(): void
    {
        $user = User::factory()->create();
        $this->post('/registro', ['first_name' => 'Ana', 'last_name' => 'Test', 'email' => strtoupper($user->email), 'password' => 'short', 'password_confirmation' => 'different'])->assertSessionHasErrors(['email', 'password']);
        $this->assertDatabaseCount('users', 1);
    }

    public static function invalidPasswordBytes(): array
    {
        return [[str_repeat('á', 40).'123'], ["Password2026!\0"]];
    }

    #[DataProvider('invalidPasswordBytes')]
    public function test_registration_rejects_passwords_that_cannot_be_safely_hashed(string $password): void
    {
        $this->post('/registro', ['first_name' => 'Ana', 'last_name' => 'Test', 'email' => 'ana@example.test', 'password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_public_password_is_always_hashed_even_if_input_looks_like_a_hash(): void
    {
        $input = Hash::make('AnOlderPassword2026!');
        $this->post('/registro', ['first_name' => 'Ana', 'last_name' => 'Test', 'email' => 'ana@example.test', 'password' => $input, 'password_confirmation' => $input])->assertRedirect('/mi-cuenta');
        $this->assertTrue(Hash::check($input, User::sole()->password));
        $this->assertFalse(Hash::check('AnOlderPassword2026!', User::sole()->password));
    }

    #[DataProvider('roles')]
    public function test_login_redirects_by_role_and_stores_session_password_marker(string $role): void
    {
        $user = User::factory()->create(['role' => UserRole::from($role)]);
        $this->post('/login', ['email' => $user->email, 'password' => 'SoloDesarrollo!2026', 'remember' => 1])
            ->assertRedirect($role === 'customer' ? '/mi-cuenta' : '/admin')->assertSessionHas('password_hash_web');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_wrong_password_is_rejected_without_flashing_it(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'Incorrect2026!'])->assertSessionHasErrors('email')->assertSessionMissing('_old_input.password');
        $this->assertGuest();
    }

    public function test_login_is_limited_after_five_failures(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'Wrong2026!']);
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'SoloDesarrollo!2026'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->post('/login', ['email' => $user->email, 'password' => 'SoloDesarrollo!2026'])->assertRedirect('/mi-cuenta');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create(['active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'SoloDesarrollo!2026'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_existing_session_is_logged_out(): void
    {
        $user = User::factory()->seller()->create();
        $this->actingAs($user)->get('/admin')->assertOk();
        $user->active = false;
        $user->save();
        $this->get('/admin')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_logout_invalidates_session_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['private_value' => 'test'])->post('/logout')->assertRedirect('/login')->assertSessionMissing('private_value');
        $this->assertGuest();
    }

    public function test_guest_routes_and_account_are_protected(): void
    {
        $this->get('/mi-cuenta')->assertRedirect('/login');
        $this->get('/admin/products')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('name="_token"', false);
        $this->get('/registro')->assertOk()->assertDontSee('name="role"', false);
        $this->get('/recuperar-contrasena')->assertOk();
    }

    public function test_csrf_remains_enforced_outside_testing_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        try {
            $this->post('/login', ['email' => 'a@example.test', 'password' => 'Password2026!'])->assertStatus(419);
        } finally {
            $this->app->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_customer_is_denied_admin_and_seller_cannot_manage_roles(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->assertFalse($user->can('manage-roles'));
        $seller = User::factory()->seller()->create();
        $this->assertFalse($seller->can('manage-roles'));
        $this->assertTrue(User::factory()->admin()->create()->can('manage-roles'));
    }

    public function test_recovery_uses_laravel_notification_and_token_can_only_be_used_once(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/recuperar-contrasena', ['email' => $user->email])->assertSessionHas('status');
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });
        $this->assertNotNull($token);
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();
        $data = ['email' => $user->email, 'token' => $token, 'password' => 'Replacement2026!', 'password_confirmation' => 'Replacement2026!'];
        $this->post('/restablecer-contrasena', $data)->assertRedirect('/login');
        $this->assertTrue(Hash::check('Replacement2026!', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->post('/restablecer-contrasena', $data)->assertSessionHasErrors('email');
    }

    public function test_array_mail_transport_composes_reset_without_real_delivery(): void
    {
        config(['mail.default' => 'array']);
        $user = User::factory()->create();
        $this->post('/recuperar-contrasena', ['email' => $user->email])->assertSessionHas('status');
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_recovery_response_does_not_reveal_missing_or_inactive_accounts(): void
    {
        Notification::fake();
        $inactive = User::factory()->create(['active' => false]);
        $this->post('/recuperar-contrasena', ['email' => 'missing@example.test'])->assertSessionHas('status');
        $this->post('/recuperar-contrasena', ['email' => $inactive->email])->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_invalid_and_expired_tokens_do_not_change_password(): void
    {
        $user = User::factory()->create();
        $before = $user->password;
        $data = ['email' => $user->email, 'token' => 'invalid', 'password' => 'Replacement2026!', 'password_confirmation' => 'Replacement2026!'];
        $this->post('/restablecer-contrasena', $data)->assertSessionHasErrors('email')->assertSessionMissing('_old_input.token')->assertSessionMissing('_old_input.password');
        $data['token'] = Password::createToken($user);
        $this->travel(61)->minutes();
        $this->post('/restablecer-contrasena', $data)->assertSessionHasErrors('email');
        $this->assertSame($before, $user->fresh()->password);
    }

    public function test_inactive_account_cannot_reset_with_an_existing_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $user->active = false;
        $user->save();
        $before = $user->password;
        $this->post('/restablecer-contrasena', ['email' => $user->email, 'token' => $token, 'password' => 'Replacement2026!', 'password_confirmation' => 'Replacement2026!'])->assertSessionHasErrors('email');
        $this->assertSame($before, $user->fresh()->password);
    }

    public function test_password_reset_revokes_existing_authenticated_session(): void
    {
        $user = User::factory()->create();
        $oldHash = $user->password;
        $token = Password::createToken($user);
        $this->post('/restablecer-contrasena', ['email' => $user->email, 'token' => $token, 'password' => 'Replacement2026!', 'password_confirmation' => 'Replacement2026!'])->assertRedirect('/login');
        $this->actingAs($user->fresh())->withSession(['password_hash_web' => $oldHash])->get('/mi-cuenta')->assertRedirect('/login');
        $this->assertGuest();
    }
}
