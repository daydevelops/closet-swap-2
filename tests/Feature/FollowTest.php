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
    // given another user exists that has followed me
    $users = User::factory()->count(3)->create();
    $this->user->follow($users[0]);
    $this->user->follow($users[1]);

    // if I query my follows
    $response = $this->getJson(route('following', auth()->id()));
    // then I should see the users that have followed me
    $response->assertJsonCount(2);
    // assert the correct users are returned
    $response->assertJsonFragment($users[0]->toArray());
    $response->assertJsonFragment($users[1]->toArray());
});

test('a user can see all users that are following them', function () {
    $users = User::factory()->count(3)->create();
    $users[0]->follow($this->user);
    $users[1]->follow($this->user);

    $response = $this->getJson(route('followers',auth()->id()));
    $response->assertJsonCount(2);
    $response->assertJsonFragment($users[0]->toArray());
    $response->assertJsonFragment($users[1]->toArray());
});

