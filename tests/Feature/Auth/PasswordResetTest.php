<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

it('can change password for authenticated user with correct current password', function () {
    // Arrange: Create an authenticated user
    $user = User::factory()->create([
        'password' => Hash::make('currentPassword123'), // Set the initial password
    ]);

    // Act: Authenticate the user via Sanctum and attempt to change password
    $response = $this->actingAs($user)->postJson('/api/password/change', [
        'current_password' => 'currentPassword123',
        'new_password' => 'newPassword123',
        'new_password_confirmation' => 'newPassword123',
    ]);

    // Assert: Check if the password was updated successfully
    $response->assertStatus(200)
        ->assertJson(['message' => 'Password changed successfully.']);

    // Verify if the password has been updated in the database
    $user->refresh();
    expect(Hash::check('newPassword123', $user->password))->toBeTrue();
});

it('cannot change password for authenticated user with incorrect current password', function () {
    // Arrange: Create an authenticated user
    $user = User::factory()->create([
        'password' => Hash::make('currentPassword123'),
    ]);

    // Act: Authenticate the user via Sanctum and attempt to change password with wrong current password
    $response = $this->actingAs($user)->postJson('/api/password/change', [
        'current_password' => 'wrongPassword123',
        'new_password' => 'newPassword123',
        'new_password_confirmation' => 'newPassword123',
    ]);

    // Assert: Ensure the response indicates the current password is incorrect
    $response->assertStatus(400)
        ->assertJson(['message' => 'Current password is incorrect.']);
});

it('can send a password reset link for a guest with valid email', function () {
    // Arrange: Create a user for testing (email must exist for validation)
    $user = User::factory()->create([
        'email' => 'user@example.com',
    ]);

    // Act: Send a POST request to request a password reset link
    $response = $this->postJson(route('password.email'), [
        'email' => 'user@example.com',
    ]);

    // Assert: Ensure the reset link was sent successfully
    $response->assertStatus(200)
        ->assertJson(['message' => trans(Password::RESET_LINK_SENT)]);
});

it('cannot send a password reset link for a guest with invalid email', function () {
    // Act: Send a POST request with a non-existing email
    $response = $this->postJson(route('password.email'), [
        'email' => 'nonexistent@example.com',
    ]);

    // Assert: Ensure the response indicates no failure
    $response->assertStatus(200)
        ->assertJson(['message' => trans(Password::RESET_LINK_SENT)]);
});
