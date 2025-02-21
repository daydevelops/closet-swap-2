<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;

it('can login with valid credentials', function () {
    // Arrange: Create a user
    $user = User::factory()->create([
        'password' => Hash::make('password123')
    ]);

    // Act: Send login request
    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    // Assert: Response contains the token
    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
        $json->has('access_token')
            ->where('token_type', 'Bearer')
        );
});

it('cannot login with invalid credentials', function () {
    // Arrange: Create a user
    $user = User::factory()->create([
        'password' => Hash::make('password123')
    ]);

    // Act: Send login request with wrong password
    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ]);

    // Assert: Expect 401 unauthorized status and error message
    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Invalid credentials'
        ]);
});

it('can logout a logged-in user', function () {
    // Arrange: Create a user and log them in
    $user = User::factory()->create([
        'password' => Hash::make('password123')
    ]);
    Sanctum::actingAs($user);

    // Act: Send logout request
    $response = $this->postJson('/api/logout');

    // Assert: Response indicates successful logout
    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Logged out successfully'
        ]);
});

it('cannot logout if not authenticated', function () {
    // Act: Send logout request without authentication
    $response = $this->postJson('/api/logout');

    // Assert: Expect 401 Unauthorized status
    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.'
        ]);
});
