<?php

use App\Models\ClothingItem;
use App\Models\User;

test('home page is displayed for a guest', function () {
    $items = \App\Models\ClothingItem::factory()->count(3)->create();
    foreach($items as $item) {
        \App\Models\ClothingItemImage::factory()->create(['clothing_item_id' => $item->id]);
    }
    $items = \App\Models\ClothingItem::with('images')->get();
    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $responseIds = collect($response->json())->pluck('id')->toArray();
    $expected_ids = $items->pluck('id')->toArray();
    $this->assertEquals($expected_ids, $responseIds);
});

test('home page is displayed with search results', function () {
    $items = \App\Models\ClothingItem::factory()->count(3)->create();
    foreach($items as $item) {
        \App\Models\ClothingItemImage::factory()->create(['clothing_item_id' => $item->id]);
    }
    $items = \App\Models\ClothingItem::with('images')->get();
    $response = $this->get(route('dashboard', ['search' => $items->first()->name]));
    $response->assertOk();
    $responseIds = collect($response->json())->pluck('id')->toArray();
    $expected_ids = [$items->first()->id];
    $this->assertEquals($expected_ids, $responseIds);
});

test('home page is displayed with filter results', function () {

});

test('a user does not see items from a user they have blocked', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $blockedUser = User::factory()->create();
    $item = ClothingItem::factory()->create(['user_id' => $blockedUser->id]);
    $blockedUser->block();
    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $responseIds = collect($response->json())->pluck('id')->toArray();
    $expected_ids = [];
    $this->assertEquals($expected_ids, $responseIds);
});

test('a user can search for other users', function () {

});

test('a user can not see a user\'s profile if they are blocked', function () {

});
