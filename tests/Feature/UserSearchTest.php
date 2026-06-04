<?php

use App\Models\Block;
use App\Models\User;

test('unauthenticated users cannot search users', function () {
    $this->getJson(route('users.search', ['q' => 'test']))->assertStatus(401);
});

test('unverified users cannot search users', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)
        ->getJson(route('users.search', ['q' => 'test']))
        ->assertStatus(403);
});

test('search requires a query param', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->getJson(route('users.search'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['q']);
});

test('search returns users matching by name', function () {
    $user = User::factory()->create();
    $match = User::factory()->create(['name' => 'UniqueSearchName']);
    User::factory()->create(['name' => 'Someone Else']);

    $response = $this->actingAs($user)
        ->getJson(route('users.search', ['q' => 'UniqueSearchName']));

    $response->assertOk();
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['name'])->toBe('UniqueSearchName');
});

test('search does not return the searching user', function () {
    $user = User::factory()->create(['name' => 'SearcherName']);

    $response = $this->actingAs($user)
        ->getJson(route('users.search', ['q' => 'SearcherName']));

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

test('search does not return blocked users', function () {
    $user = User::factory()->create();
    $blocked = User::factory()->create(['name' => 'BlockedPerson']);

    Block::create(['blocked_by' => $user->id, 'blocked_id' => $blocked->id]);

    $response = $this->actingAs($user)
        ->getJson(route('users.search', ['q' => 'BlockedPerson']));

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

test('search does not return users who blocked the searcher', function () {
    $user = User::factory()->create();
    $blocker = User::factory()->create(['name' => 'BlockerPerson']);

    Block::create(['blocked_by' => $blocker->id, 'blocked_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson(route('users.search', ['q' => 'BlockerPerson']));

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

test('search returns paginated results', function () {
    $user = User::factory()->create();
    User::factory()->count(5)->create(['name' => 'PaginatedUser']);

    $response = $this->actingAs($user)
        ->getJson(route('users.search', ['q' => 'PaginatedUser']));

    $response->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'total', 'per_page']);
});

test('search results only contain public-safe fields', function () {
    $user = User::factory()->create();
    User::factory()->create(['name' => 'SafeFieldsUser']);

    $response = $this->actingAs($user)
        ->getJson(route('users.search', ['q' => 'SafeFieldsUser']));

    $result = $response->json('data.0');
    expect($result)->toHaveKeys(['id', 'name']);
    expect($result)->not->toHaveKey('email');
    expect($result)->not->toHaveKey('password');
});
