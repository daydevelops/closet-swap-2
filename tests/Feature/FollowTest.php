<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('a user can follow a user', function () {
    // given another user exists that I have not followed
    $otherUser = User::factory()->create();
    // when I send a POST request to the endpoint
    $response = $this->postJson(route('follow', $otherUser));
    // then the response should be successful
    $response->assertSuccessful();
});

test('a user can unfollow a user', function () {
    // given another user exists that I have followed
    $otherUser = User::factory()->create();
    $this->user->follow($otherUser);
    // when I send a DELETE request to the endpoint
    $response = $this->deleteJson(route('unfollow', $otherUser));
    // then the response should be successful
    $response->assertSuccessful();
});

test('a user can see all users that have followed them', function () {
    $users = User::factory()->count(3)->create();
    $this->user->follow($users[0]);
    $this->user->follow($users[1]);

    $response = $this->getJson(route('following', auth()->id()));

    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('data.0.id', $users[0]->id);
    $response->assertJsonPath('data.1.id', $users[1]->id);
});

test('a user can see all users that are following them', function () {
    $users = User::factory()->count(3)->create();
    $users[0]->follow($this->user);
    $users[1]->follow($this->user);

    $response = $this->getJson(route('followers', auth()->id()));

    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('data.0.id', $users[0]->id);
    $response->assertJsonPath('data.1.id', $users[1]->id);
});

