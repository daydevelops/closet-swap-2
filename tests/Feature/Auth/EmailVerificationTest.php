<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

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

it('a user can verify their email with a valid signed link', function () {
    $user = User::factory()->unverified()->create();

    $signedUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );
    $params = [];
    parse_str(parse_url($signedUrl, PHP_URL_QUERY), $params);

    $response = $this->actingAs($user)
        ->getJson(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]) . '?expires=' . $params['expires'] . '&signature=' . $params['signature']);

    $response->assertStatus(200)->assertJson(['message' => 'verified']);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('verifying an already verified email returns already_verified', function () {
    $user = User::factory()->create(); // factory creates verified users by default

    $signedUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );
    $params = [];
    parse_str(parse_url($signedUrl, PHP_URL_QUERY), $params);

    $response = $this->actingAs($user)
        ->getJson(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]) . '?expires=' . $params['expires'] . '&signature=' . $params['signature']);

    $response->assertStatus(200)->assertJson(['message' => 'already_verified']);
});

test('registration sends a verification email', function () {
    Notification::fake();

    $response = $this->postJson(route('register'), [
        'name'                  => 'Test User',
        'email'                 => 'verify@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
        'agreed_to_guidelines'  => true,
    ]);

    $response->assertStatus(201);
    $user = User::where('email', 'verify@example.com')->first();
    Notification::assertSentTo($user, VerifyEmail::class);
});
