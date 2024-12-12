<?php

use App\Models\User;

test('a user knows if it has blocked another user', function () {
    // given I am an authenticated user
    $user = User::factory()->create();
    $this->actingAs($user);
    // when I block another user
    $blockedUser = User::factory()->create();
    $user->block($blockedUser);
    // then I should know that I have blocked that user
    $this->assertTrue($user->isBlocked($blockedUser));
});

test('a user can block another user', function () {
    // given I am an authenticated user
    $user = User::factory()->create();
    $this->actingAs($user);
    // when I request a block for another user
    $blockedUser = User::factory()->create();
    $this->assertFalse($blockedUser->isBlocked());
    $this->post(route('block',$blockedUser->id));
    // then the other user should be blocked
    $this->assertTrue($blockedUser->isBlocked());
});

test('a user can unblock another user', function () {
    // given I am an authenticated user
    $user = User::factory()->create();
    $this->actingAs($user);
    // when I unblock another user
    $blockedUser = User::factory()->create();
    $blockedUser->block();
    $this->assertTrue($blockedUser->isBlocked());
    $this->delete(route('block',$blockedUser->id));
    // then the other user should be unblocked
    $this->assertFalse($blockedUser->isBlocked());
});

test('a user can see all users that they have blocked', function () {
    // given I am an authenticated user
    $user = User::factory()->create();
    $this->actingAs($user);
    // when I block multiple users
    $blockedUsers = User::factory()->count(3)->create();
    foreach ($blockedUsers as $blockedUser) {
        $blockedUser->block();
    }
    // then I should see all the users that I have blocked
    $this->assertCount(3, $user->blocks);
});

