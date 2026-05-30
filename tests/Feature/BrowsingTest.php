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
    $uniqueBrand = 'BrandFilter-' . uniqid();
    $matchingItem = \App\Models\ClothingItem::factory()->create(['brand' => $uniqueBrand, 'status' => 'available']);
    $otherItem    = \App\Models\ClothingItem::factory()->create(['brand' => 'OtherBrand', 'status' => 'available']);

    $response = $this->get(route('dashboard', ['filters' => ['brand' => $uniqueBrand]]));
    $response->assertOk();
    $responseIds = collect($response->json())->pluck('id')->toArray();

    // The matching item should appear
    $this->assertContains($matchingItem->id, $responseIds);
    // The other item should not appear
    $this->assertNotContains($otherItem->id, $responseIds);
});

test('disallowed filter keys are ignored', function () {
    $user = \App\Models\User::factory()->create();
    $item = \App\Models\ClothingItem::factory()->create(['user_id' => $user->id]);

    // Filter on user_id (not in allowlist) — should be ignored, item must still appear
    $otherUser = \App\Models\User::factory()->create();
    $response  = $this->get(route('dashboard', ['filters' => ['user_id' => $otherUser->id]]));
    $response->assertOk();
    $responseIds = collect($response->json())->pluck('id')->toArray();

    $this->assertContains($item->id, $responseIds);
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

test('a user can not see a user\'s profile items if they are blocked', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $blockedUser = User::factory()->create();
    $item = ClothingItem::factory()->create(['user_id' => $blockedUser->id]);
    $blockedUser->block();

    $response = $this->getJson(route('profile.items', $blockedUser));
    $response->assertOk();
    $responseIds = collect($response->json())->pluck('id')->toArray();
    $this->assertNotContains($item->id, $responseIds);
});

test('a user only sees items belonging to the viewed user on their profile', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $profileUser  = User::factory()->create();
    $profileItem  = ClothingItem::factory()->create(['user_id' => $profileUser->id]);
    $otherItem    = ClothingItem::factory()->create(); // belongs to a different user

    $response = $this->getJson(route('profile.items', $profileUser));
    $response->assertOk();
    $responseIds = collect($response->json())->pluck('id')->toArray();
    $this->assertContains($profileItem->id, $responseIds);
    $this->assertNotContains($otherItem->id, $responseIds);
});
