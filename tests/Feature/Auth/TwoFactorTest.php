<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function userWith2fa(array $recoveryCodes = ['aaaa-1111', 'bbbb-2222']): User
    {
        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => Google2FA::generateSecretKey(),
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user->fresh();
    }

    // ---------------------------------------------------------------- gestion (profil)

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('two-factor.show'))->assertRedirect(route('login'));
        $this->post(route('two-factor.enable'))->assertRedirect(route('login'));
    }

    public function test_show_without_secret_has_no_qr_code(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('two-factor.show'))
            ->assertOk()
            ->assertViewIs('profile.two-factor')
            ->assertViewHas('qrSvg', null);
    }

    public function test_enable_generates_a_secret_and_the_show_page_renders_a_qr_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('two-factor.enable'))->assertRedirect(route('two-factor.show'));

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertFalse($user->hasTwoFactorEnabled());

        $this->get(route('two-factor.show'))
            ->assertOk()
            ->assertViewHas('qrSvg', fn ($svg) => is_string($svg) && str_contains($svg, '<svg'));
    }

    public function test_confirm_with_the_right_code_enables_two_factor_and_creates_recovery_codes(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['two_factor_secret' => Google2FA::generateSecretKey()])->save();

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => Google2FA::getCurrentOtp($user->two_factor_secret)])
            ->assertRedirect(route('two-factor.show'))
            ->assertSessionHas('status', '2FA activée avec succès.');

        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertCount(8, $user->two_factor_recovery_codes);
    }

    public function test_confirm_rejects_a_wrong_code_a_malformed_code_and_a_missing_secret(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // pas de secret
        $this->post(route('two-factor.confirm'), ['code' => '123456'])->assertSessionHasErrors('code');

        $user->forceFill(['two_factor_secret' => Google2FA::generateSecretKey()])->save();
        $good = Google2FA::getCurrentOtp($user->two_factor_secret);
        $bad = $good === '000000' ? '111111' : '000000';

        $this->post(route('two-factor.confirm'), ['code' => $bad])
            ->assertSessionHasErrors(['code' => 'Code invalide, réessaie.']);
        $this->post(route('two-factor.confirm'), ['code' => 'abc'])->assertSessionHasErrors('code');
        $this->post(route('two-factor.confirm'), [])->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_regenerate_requires_two_factor_and_replaces_the_codes(): void
    {
        $plain = User::factory()->create();
        $this->actingAs($plain)->post(route('two-factor.recovery'))->assertForbidden();

        $user = $this->userWith2fa();
        $this->actingAs($user)->post(route('two-factor.recovery'))
            ->assertRedirect(route('two-factor.show'))
            ->assertSessionHas('status', 'Nouveaux codes de récupération générés.');

        $codes = $user->fresh()->two_factor_recovery_codes;
        $this->assertCount(8, $codes);
        $this->assertNotContains('aaaa-1111', $codes);
    }

    public function test_disable_requires_the_current_password(): void
    {
        $user = $this->userWith2fa();
        $this->actingAs($user);

        $this->delete(route('two-factor.disable'), ['password' => 'mauvais'])->assertSessionHasErrors('password');
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->delete(route('two-factor.disable'), ['password' => 'password'])
            ->assertRedirect(route('two-factor.show'))
            ->assertSessionHas('status', '2FA désactivée.');

        $user->refresh();
        $this->assertFalse($user->hasTwoFactorEnabled());
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
    }

    // ---------------------------------------------------------------- connexion avec 2FA

    public function test_login_with_two_factor_redirects_to_the_challenge_without_logging_in(): void
    {
        $user = $this->userWith2fa();

        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => 'on'])
            ->assertRedirect(route('two-factor.challenge'))
            ->assertSessionHas('2fa.user_id', $user->id)
            ->assertSessionHas('2fa.remember', true);

        $this->assertGuest();
    }

    public function test_challenge_page_requires_a_pending_login(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
        $this->post(route('two-factor.challenge'), ['code' => '123456'])->assertRedirect(route('login'));

        $this->withSession(['2fa.user_id' => $this->userWith2fa()->id])
            ->get(route('two-factor.challenge'))
            ->assertOk()
            ->assertViewIs('auth.two-factor-challenge');
    }

    public function test_challenge_with_a_valid_code_logs_the_user_in(): void
    {
        $user = $this->userWith2fa();

        $this->withSession(['2fa.user_id' => $user->id, '2fa.remember' => true])
            ->post(route('two-factor.challenge'), ['code' => Google2FA::getCurrentOtp($user->two_factor_secret)])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionMissing('2fa.user_id');

        $this->assertAuthenticatedAs($user);
    }

    public function test_challenge_with_a_recovery_code_logs_in_and_consumes_the_code(): void
    {
        $user = $this->userWith2fa(['aaaa-1111', 'bbbb-2222']);

        $this->withSession(['2fa.user_id' => $user->id])
            ->post(route('two-factor.challenge'), ['recovery_code' => 'aaaa-1111'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(['bbbb-2222'], $user->fresh()->two_factor_recovery_codes);
    }

    public function test_challenge_rejects_invalid_codes(): void
    {
        $user = $this->userWith2fa();
        $bad = Google2FA::getCurrentOtp($user->two_factor_secret) === '000000' ? '111111' : '000000';

        $this->withSession(['2fa.user_id' => $user->id])
            ->post(route('two-factor.challenge'), ['code' => $bad])
            ->assertSessionHasErrors(['code' => 'Code invalide.']);
        $this->post(route('two-factor.challenge'), ['recovery_code' => 'inconnu'])
            ->assertSessionHasErrors('code');
        $this->post(route('two-factor.challenge'), [])->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertCount(2, $user->fresh()->two_factor_recovery_codes);
    }

    // ---------------------------------------------------------------- LoginRequest (anti brute-force)

    public function test_login_is_locked_after_five_failed_attempts(): void
    {
        Event::fake([Lockout::class]);
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'mauvais'])->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        Event::assertDispatched(Lockout::class);
    }

    public function test_successful_login_clears_the_failed_attempts(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'mauvais']);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }
}
