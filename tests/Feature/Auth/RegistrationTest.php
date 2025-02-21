<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;

it('can register a user with valid data', function () {
    // Arrange: Prepare valid registration data
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    // Act: Send a POST request to register the user
    $response = $this->postJson('/api/register', $data);

    // Assert: Check if the response is successful and the user is created
    $response->assertStatus(201)
        ->assertJson(fn (AssertableJson $json) =>
        $json->has('user')
            ->has('access_token')
            ->where('token_type', 'Bearer')
            ->where('message', 'User successfully registered.')
        );
});

it('cannot register a user with missing required fields', function () {
    // Act: Send a POST request with missing data (e.g., missing password)
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    // Assert: Check if validation error response is returned
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('cannot register a user with an already existing email', function () {
    // Arrange: Create a user with the email 'duplicate@example.com'
    User::create([
        'name' => 'Existing User',
        'email' => 'duplicate@example.com',
        'password' => Hash::make('password123'),
    ]);

    // Act: Attempt to register a new user with the same email
    $response = $this->postJson('/api/register', [
        'name' => 'New User',
        'email' => 'duplicate@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    // Assert: Ensure the response returns a validation error for the email
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
