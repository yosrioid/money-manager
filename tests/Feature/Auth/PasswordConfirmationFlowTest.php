<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('password can be confirmed with the correct password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect();

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

test('password confirmation fails with the wrong password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('password.confirm.store'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');
});

test('confirmed password status returns true after confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('password.confirmation'))
        ->assertOk()
        ->assertJson(['confirmed' => true]);
});

test('confirmed password status returns false without confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('password.confirmation'))
        ->assertOk()
        ->assertJson(['confirmed' => false]);
});

test('sensitive routes redirect to password confirm when session is expired', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertRedirect(route('password.confirm'));
});

test('sensitive routes are accessible after password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk();
});
