<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('an unverified user cannot access protected routes', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->getJson(route('profile.show', $user));

    $response->assertStatus(403);
});

test('a verified user can access protected routes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('profile.show', $user));

    $response->assertOk();
});

test('an unverified user can still logout', function () {
    $user  = User::factory()->unverified()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->postJson(route('logout'));

    $response->assertOk();
});

test('an unverified user can resend a verification email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->postJson(route('verification.send'));

    $response->assertOk();
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('resending when already verified returns a message without sending', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('verification.send'));

    $response->assertOk()->assertJson(['message' => 'Email already verified.']);
    Notification::assertNothingSent();
});

test('registration sends a verification email', function () {
    Notification::fake();

    $response = $this->postJson(route('register'), [
        'name'                  => 'Test User',
        'email'                 => 'verify@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(201);
    $user = User::where('email', 'verify@example.com')->first();
    Notification::assertSentTo($user, VerifyEmail::class);
});
