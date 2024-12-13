<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('a user can see all users that have liked their clothing item', function () {

});

test('a user can see all the items they have liked', function () {
    $this->assertTrue(0);
});

test('a user can like a clothing item', function () {
    $this->assertTrue(0);
});

test('a user can unlike a clothing item', function () {
    $this->assertTrue(0);
});
