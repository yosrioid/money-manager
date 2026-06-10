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

test('two factor authentication can be enabled', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->json('POST', route('two-factor.enable'))
        ->assertStatus(200);

    expect($user->fresh()->two_factor_secret)->not->toBeNull();
});

test('two factor qr code can be viewed after enabling', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->json('POST', route('two-factor.enable'));

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->json('GET', route('two-factor.qr-code'))
        ->assertOk()
        ->assertJsonStructure(['svg']);
});

test('two factor secret key can be viewed after enabling', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->json('POST', route('two-factor.enable'));

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->json('GET', route('two-factor.secret-key'))
        ->assertOk()
        ->assertJsonStructure(['secretKey']);
});

test('two factor authentication can be confirmed with a valid code', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->json('POST', route('two-factor.enable'));

    $secret = decrypt($user->fresh()->two_factor_secret);
    $code = (new Google2FA)->getCurrentOtp($secret);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->json('POST', route('two-factor.confirm'), ['code' => $code])
        ->assertStatus(200);

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('two factor confirmation fails with an invalid code', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->json('POST', route('two-factor.enable'));

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->json('POST', route('two-factor.confirm'), ['code' => '000000']);

    expect($response->status())->toBeIn([302, 422])
        ->and($user->fresh()->two_factor_confirmed_at)->toBeNull();
});

test('recovery codes can be viewed after enabling two factor', function () {
    $user = User::factory()->withTwoFactor()->create();

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->json('GET', route('two-factor.recovery-codes'))
        ->assertOk();

    expect($response->json())->toBeArray()->not->toBeEmpty();
});

test('recovery codes can be regenerated', function () {
    $user = User::factory()->withTwoFactor()->create();

    $originalCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->json('POST', route('two-factor.regenerate-recovery-codes'))
        ->assertStatus(200);

    $newCodes = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);

    expect($newCodes)->not->toBe($originalCodes);
});

test('two factor authentication can be disabled', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->json('DELETE', route('two-factor.disable'))
        ->assertStatus(200);

    expect($user->fresh()->two_factor_secret)->toBeNull()
        ->and($user->fresh()->two_factor_confirmed_at)->toBeNull();
});

test('unauthenticated user cannot enable two factor', function () {
    $this->post(route('two-factor.enable'))
        ->assertRedirect(route('login'));
});

test('unauthenticated user cannot disable two factor', function () {
    $this->delete(route('two-factor.disable'))
        ->assertRedirect(route('login'));
});

test('two factor management redirects to password confirm when confirmation is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('two-factor.enable'))
        ->assertRedirect(route('password.confirm'));
});
