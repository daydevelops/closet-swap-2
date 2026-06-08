<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;

it('can register a user with valid data', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agreed_to_guidelines' => true,
    ];

    $response = $this->postJson('/api/register', $data);

    $response->assertStatus(201)
        ->assertJson(fn (AssertableJson $json) =>
        $json->has('user')
            ->has('access_token')
            ->where('token_type', 'Bearer')
            ->where('message', 'User successfully registered.')
        );
});

it('cannot register a user with missing required fields', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('cannot register a user with an already existing email', function () {
    User::create([
        'name' => 'Existing User',
        'email' => 'duplicate@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/register', [
        'name' => 'New User',
        'email' => 'duplicate@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agreed_to_guidelines' => true,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('cannot register without agreeing to guidelines', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agreed_to_guidelines' => false,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['agreed_to_guidelines']);
});

it('cannot register when guidelines field is missing', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['agreed_to_guidelines']);
});
