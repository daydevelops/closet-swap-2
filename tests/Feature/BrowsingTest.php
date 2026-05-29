<?php

use App\Models\ClothingItem;
use App\Models\User;

test('home page is displayed for a guest', function () {
    $items = \App\Models\ClothingItem::factory()->count(3)->create();
    foreach($items as $item) {
        \App\Models\ClothingItemImage::factory()->create(['clothing_item_id' => $item->id]);
    }
    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $responseIds = collect($response->json())->pluck('id')->toArray();
    // Assert the 3 created items are present (other seed items may also be in the response)
    foreach ($items->pluck('id') as $id) {
        $this->assertContains($id, $responseIds);
    }
});

test('home page is displayed with search results', function () {
    // Use a unique title that won't match any seed data
    $uniqueTitle = 'ZZZ-Test-Item-' . uniqid();
    $item = \App\Models\ClothingItem::factory()->create(['title' => $uniqueTitle]);
    \App\Models\ClothingItemImage::factory()->create(['clothing_item_id' => $item->id]);

    $response = $this->get(route('dashboard', ['search' => $uniqueTitle]));
    $response->assertOk();
    $responseIds = collect($response->json())->pluck('id')->toArray();
    $this->assertContains($item->id, $responseIds);
    $this->assertCount(1, $responseIds);
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
    // The blocked user's item should not appear in the feed
    $this->assertNotContains($item->id, $responseIds);
});

test('a user can search for other users', function () {

});

test('a user can not see a user\'s profile if they are blocked', function () {

});
