<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('profile page is displayed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->getJson(route('profile.show', $user));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->patchJson(route('profile.update'), [
        'name' => 'Updated Name',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
});

test('user can delete their account', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $this->actingAs($user);

    $response = $this->deleteJson(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('a user can update their bio', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->patchJson(route('profile.update'), [
        'name'  => $user->name,
        'email' => $user->email,
        'bio'   => 'She/her. Based in Montreal.',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'bio' => 'She/her. Based in Montreal.']);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $this->actingAs($user);

    $response = $this->deleteJson(route('profile.destroy'), [
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseHas('users', ['id' => $user->id]);
});
