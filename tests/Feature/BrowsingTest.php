<?php

use App\Models\ClothingItem;
use App\Models\ClothingItemImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('home page is displayed for a guest', function () {
    Storage::fake('s3');
    $items = \App\Models\ClothingItem::factory()->count(3)->create();
    foreach($items as $item) {
        \App\Models\ClothingItemImage::factory()->create(['clothing_item_id' => $item->id]);
    }
    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $responseIds = collect($response->json('data'))->pluck('id')->toArray();
    // Assert the 3 created items are present (other seed items may also be in the response)
    foreach ($items->pluck('id') as $id) {
        $this->assertContains($id, $responseIds);
    }
});

test('home page is displayed with search results', function () {
    Storage::fake('s3');
    // Use a unique title that won't match any seed data
    $uniqueTitle = 'ZZZ-Test-Item-' . uniqid();
    $item = \App\Models\ClothingItem::factory()->create(['title' => $uniqueTitle]);
    \App\Models\ClothingItemImage::factory()->create(['clothing_item_id' => $item->id]);

    $response = $this->get(route('dashboard', ['search' => $uniqueTitle]));
    $response->assertOk();
    $responseIds = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertContains($item->id, $responseIds);
    $this->assertCount(1, $responseIds);
});

test('home page is displayed with filter results', function () {
    $uniqueBrand = 'BrandFilter-' . uniqid();
    $matchingItem = \App\Models\ClothingItem::factory()->create(['brand' => $uniqueBrand, 'status' => 'available']);
    $otherItem    = \App\Models\ClothingItem::factory()->create(['brand' => 'OtherBrand', 'status' => 'available']);

    $response = $this->get(route('dashboard', ['filters' => ['brand' => $uniqueBrand]]));
    $response->assertOk();
    $responseIds = collect($response->json('data'))->pluck('id')->toArray();

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
    $responseIds = collect($response->json('data'))->pluck('id')->toArray();

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
    $responseIds = collect($response->json('data'))->pluck('id')->toArray();
    // The blocked user's item should not appear in the feed
    $this->assertNotContains($item->id, $responseIds);
});

test('feed items include a signed image url', function () {
    Storage::fake('s3');
    $item = ClothingItem::factory()->create();
    $image = ClothingItemImage::factory()->create(['clothing_item_id' => $item->id]);

    $response = $this->getJson(route('dashboard'));
    $response->assertOk();

    $responseItem = collect($response->json('data'))->firstWhere('id', $item->id);
    expect($responseItem['images'])->toHaveCount(1)
        ->and($responseItem['images'][0]['signed_url'])->not->toBeNull();
});

test('a user can search for other users', function () {

});

test('for-you feed returns items that share a tag with a liked item', function () {
    Storage::fake('s3');

    $tag = \App\Models\CiTags::inRandomOrder()->first();

    $liker = User::factory()->create();
    $owner = User::factory()->create();

    // Item the user liked — has $tag
    $likedItem = \App\Models\ClothingItem::factory()->create([
        'user_id' => $owner->id,
        'status'  => 'available',
    ]);
    $likedItem->tags()->sync([$tag->id]);

    // Another item by $owner with same tag — should appear in for-you
    $matchingItem = \App\Models\ClothingItem::factory()->create([
        'user_id' => $owner->id,
        'status'  => 'available',
    ]);
    $matchingItem->tags()->sync([$tag->id]);

    $liker->likes()->attach($likedItem->id);

    $this->actingAs($liker);
    $response = $this->getJson(route('dashboard', ['sort' => 'for-you']));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertContains($matchingItem->id, $ids);
});

test('for-you feed excludes the authenticated user\'s own items', function () {
    $tag = \App\Models\CiTags::inRandomOrder()->first();

    $user  = User::factory()->create();
    $other = User::factory()->create();

    $likedItem = \App\Models\ClothingItem::factory()->create(['user_id' => $other->id, 'status' => 'available']);
    $likedItem->tags()->sync([$tag->id]);

    // User's own item with the same tag — should be excluded
    $ownItem = \App\Models\ClothingItem::factory()->create(['user_id' => $user->id, 'status' => 'available']);
    $ownItem->tags()->sync([$tag->id]);

    $user->likes()->attach($likedItem->id);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard', ['sort' => 'for-you']));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertNotContains($ownItem->id, $ids);
});

test('for-you feed excludes items the user has already liked', function () {
    $tag = \App\Models\CiTags::inRandomOrder()->first();

    $user  = User::factory()->create();
    $other = User::factory()->create();

    $likedItem = \App\Models\ClothingItem::factory()->create(['user_id' => $other->id, 'status' => 'available']);
    $likedItem->tags()->sync([$tag->id]);

    $user->likes()->attach($likedItem->id);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard', ['sort' => 'for-you']));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertNotContains($likedItem->id, $ids);
});

test('for-you feed only shows available items', function () {
    $tag = \App\Models\CiTags::inRandomOrder()->first();

    $user  = User::factory()->create();
    $other = User::factory()->create();

    $likedItem = \App\Models\ClothingItem::factory()->create(['user_id' => $other->id, 'status' => 'available']);
    $likedItem->tags()->sync([$tag->id]);

    $unavailableItem = \App\Models\ClothingItem::factory()->create(['user_id' => $other->id, 'status' => 'sold']);
    $unavailableItem->tags()->sync([$tag->id]);

    $user->likes()->attach($likedItem->id);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard', ['sort' => 'for-you']));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertNotContains($unavailableItem->id, $ids);
});

test('for-you feed falls back to latest feed when user has no likes', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $item = \App\Models\ClothingItem::factory()->create(['status' => 'available']);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard', ['sort' => 'for-you']));
    $response->assertOk();

    // Feed should still return results (not crash) and include the item
    $ids = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertContains($item->id, $ids);
});

test('for-you feed falls back to latest for unauthenticated users', function () {
    Storage::fake('s3');

    $item = \App\Models\ClothingItem::factory()->create(['status' => 'available']);
    \App\Models\ClothingItemImage::factory()->create(['clothing_item_id' => $item->id]);

    $response = $this->getJson(route('dashboard', ['sort' => 'for-you']));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertContains($item->id, $ids);
});

test('a user can not see a user\'s profile items if they are blocked', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $blockedUser = User::factory()->create();
    $item = ClothingItem::factory()->create(['user_id' => $blockedUser->id]);
    $blockedUser->block();

    $response = $this->getJson(route('profile.items', $blockedUser));
    $response->assertOk();
    $responseIds = collect($response->json('data'))->pluck('id')->toArray();
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
    $responseIds = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertContains($profileItem->id, $responseIds);
    $this->assertNotContains($otherItem->id, $responseIds);
});
