<?php

use App\Models\User;

test('an unknown api route returns a json 404', function () {
    $response = $this->getJson('/api/this-route-does-not-exist');
    $response->assertStatus(404)->assertJsonStructure(['message']);
});

test('a missing model returns a json 404', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->getJson('/api/items/99999999');
    $response->assertStatus(404)->assertJsonStructure(['message']);
});
