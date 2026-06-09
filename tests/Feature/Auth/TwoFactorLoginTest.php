<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! Features::enabled(Features::twoFactorAuthentication())) {
        $this->markTestSkipped('Two-factor authentication feature is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);
});

/**
 * Set up a session that is pending the two-factor challenge for $user.
 */
function beginTwoFactorLogin(User $user): void
{
    test()->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);
}

test('two factor challenge passes with a valid totp code', function () {
    $secret = (new Google2FA)->generateSecretKey();
    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode([])),
        'two_factor_confirmed_at' => now(),
    ]);

    beginTwoFactorLogin($user);

    $code = (new Google2FA)->getCurrentOtp($secret);

    $this->post(route('two-factor.login.store'), ['code' => $code])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('two factor challenge fails with an invalid totp code', function () {
    $secret = (new Google2FA)->generateSecretKey();
    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode([])),
        'two_factor_confirmed_at' => now(),
    ]);

    beginTwoFactorLogin($user);

    $this->post(route('two-factor.login.store'), ['code' => '000000'])
        ->assertSessionHasErrors();

    $this->assertGuest();
});

test('two factor challenge passes with a valid recovery code', function () {
    $recoveryCode = 'valid-recovery-code-1';
    $user = User::factory()->create([
        'two_factor_secret' => encrypt((new Google2FA)->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode([$recoveryCode])),
        'two_factor_confirmed_at' => now(),
    ]);

    beginTwoFactorLogin($user);

    $this->post(route('two-factor.login.store'), ['recovery_code' => $recoveryCode])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('two factor challenge fails with an invalid recovery code', function () {
    $user = User::factory()->withTwoFactor()->create();

    beginTwoFactorLogin($user);

    $this->post(route('two-factor.login.store'), ['recovery_code' => 'wrong-code'])
        ->assertSessionHasErrors();

    $this->assertGuest();
});
