<?php

use App\Models\ClothingItem;
use App\Models\ClothingItemImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('home page is displayed for a guest', function () {
    Storage::fake('s3');
    $items = \App\Models\ClothingItem::factory()->count(3)->create(['status' => 'available']);
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
    $item = \App\Models\ClothingItem::factory()->create(['title' => 'Unique Vintage Dress', 'status' => 'available']);
    \App\Models\ClothingItemImage::factory()->create(['clothing_item_id' => $item->id]);

    // FULLTEXT indexes (used by whereFullText) are not updated within a database
    // transaction, so we can only assert the endpoint accepts the search param and responds
    // successfully — not which specific rows are returned.
    $response = $this->get(route('dashboard', ['search' => 'Vintage']));
    $response->assertOk();
    $response->assertJsonStructure(['data']);
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
    $item = \App\Models\ClothingItem::factory()->create(['user_id' => $user->id, 'status' => 'available']);

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
    $item = ClothingItem::factory()->create(['user_id' => $blockedUser->id, 'status' => 'available']);
    $blockedUser->block();
    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $responseIds = collect($response->json('data'))->pluck('id')->toArray();
    // The blocked user's item should not appear in the feed
    $this->assertNotContains($item->id, $responseIds);
});

test('feed items include a signed image url', function () {
    Storage::fake('s3');
    $item = ClothingItem::factory()->create(['status' => 'available']);
    $image = ClothingItemImage::factory()->create(['clothing_item_id' => $item->id]);

    $response = $this->getJson(route('dashboard'));
    $response->assertOk();

    $responseItem = collect($response->json('data'))->firstWhere('id', $item->id);
    expect($responseItem['images'])->toHaveCount(1)
        ->and($responseItem['images'][0]['signed_url'])->not->toBeNull();
});

test('latest feed only shows available items', function () {
    $availableItem   = ClothingItem::factory()->create(['status' => 'available']);
    $soldItem        = ClothingItem::factory()->create(['status' => 'sold']);
    $donatedItem     = ClothingItem::factory()->create(['status' => 'donated']);

    $response = $this->getJson(route('dashboard'));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertContains($availableItem->id, $ids);
    $this->assertNotContains($soldItem->id, $ids);
    $this->assertNotContains($donatedItem->id, $ids);
});

test('latest feed excludes the authenticated user\'s own items', function () {
    $user      = User::factory()->create();
    $ownItem   = ClothingItem::factory()->create(['user_id' => $user->id, 'status' => 'available']);
    $otherItem = ClothingItem::factory()->create(['status' => 'available']);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard'));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertNotContains($ownItem->id, $ids);
    $this->assertContains($otherItem->id, $ids);
});

test('latest feed shows all items to guests', function () {
    $item = ClothingItem::factory()->create(['status' => 'available']);

    $response = $this->getJson(route('dashboard'));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertContains($item->id, $ids);
});

test('trending feed only shows available items', function () {
    // Give both items enough likes to rank on page 1 above existing seed data
    $likers        = User::factory()->count(30)->create();
    $availableItem = ClothingItem::factory()->create(['status' => 'available']);
    $soldItem      = ClothingItem::factory()->create(['status' => 'sold']);
    foreach ($likers as $liker) {
        $liker->likes()->attach([$availableItem->id, $soldItem->id]);
    }

    $response = $this->getJson(route('dashboard', ['sort' => 'trending']));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertContains($availableItem->id, $ids);
    $this->assertNotContains($soldItem->id, $ids);
});

test('trending feed excludes the authenticated user\'s own items', function () {
    $user    = User::factory()->create();
    $likers  = User::factory()->count(30)->create();
    $ownItem = ClothingItem::factory()->create(['user_id' => $user->id, 'status' => 'available']);
    $otherItem = ClothingItem::factory()->create(['status' => 'available']);
    foreach ($likers as $liker) {
        $liker->likes()->attach([$ownItem->id, $otherItem->id]);
    }

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard', ['sort' => 'trending']));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertNotContains($ownItem->id, $ids);
    $this->assertContains($otherItem->id, $ids);
});

test('trending feed shows all available items to guests', function () {
    $likers = User::factory()->count(30)->create();
    $item   = ClothingItem::factory()->create(['status' => 'available']);
    foreach ($likers as $liker) {
        $liker->likes()->attach($item->id);
    }

    $response = $this->getJson(route('dashboard', ['sort' => 'trending']));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    $this->assertContains($item->id, $ids);
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

test('for-you feed ranks tag-matched items above non-matching items', function () {
    // Pick two distinct tags so there's no accidental overlap
    $tags       = \App\Models\CiTags::inRandomOrder()->take(2)->get();
    $likedTag   = $tags->first();
    $otherTag   = $tags->last();

    $liker = User::factory()->create();
    $owner = User::factory()->create();

    // Item the user liked — tagged with $likedTag only
    $likedItem = \App\Models\ClothingItem::factory()->create(['user_id' => $owner->id, 'status' => 'available']);
    $likedItem->tags()->sync([$likedTag->id]);

    // Matching item (older) — should rank above the non-matching newer item
    $matchingItem = \App\Models\ClothingItem::factory()->create([
        'user_id'    => $owner->id,
        'status'     => 'available',
        'created_at' => now()->subMinutes(10),
    ]);
    $matchingItem->tags()->sync([$likedTag->id]);

    // Non-matching item (newer) — appears in feed but ranked below matching item
    $nonMatchingItem = \App\Models\ClothingItem::factory()->create([
        'user_id'    => $owner->id,
        'status'     => 'available',
        'created_at' => now()->subMinutes(1),
    ]);
    $nonMatchingItem->tags()->sync([$otherTag->id]);

    $liker->likes()->attach($likedItem->id);

    $this->actingAs($liker);

    // Collect all pages to find both items regardless of feed volume
    $allIds = collect();
    $page   = 1;
    do {
        $resp   = $this->getJson(route('dashboard', ['sort' => 'for-you', 'page' => $page]));
        $resp->assertOk();
        $pageIds = collect($resp->json('data'))->pluck('id');
        $allIds  = $allIds->concat($pageIds);
        $lastPage = $resp->json('last_page');
        $page++;
    } while ($page <= $lastPage);

    $matchingPos    = $allIds->search($matchingItem->id);
    $nonMatchingPos = $allIds->search($nonMatchingItem->id);

    $this->assertNotFalse($matchingPos);
    $this->assertNotFalse($nonMatchingPos);
    expect($matchingPos)->toBeLessThan($nonMatchingPos);
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

test('latest feed surfaces followed users\' items first', function () {
    $user    = User::factory()->create();
    $followed = User::factory()->create();
    $other    = User::factory()->create();

    // Give followed user an older item and other user a newer one
    $followedItem = ClothingItem::factory()->create([
        'user_id'    => $followed->id,
        'status'     => 'available',
        'created_at' => now()->subMinutes(10),
    ]);
    $otherItem = ClothingItem::factory()->create([
        'user_id'    => $other->id,
        'status'     => 'available',
        'created_at' => now()->subMinutes(1),
    ]);

    $user->followings()->attach($followed->id);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard'));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->values()->toArray();
    $followedPos = array_search($followedItem->id, $ids);
    $otherPos    = array_search($otherItem->id, $ids);

    // Followed user's older item should rank above the non-followed newer item
    expect($followedPos)->toBeLessThan($otherPos);
});

test('latest feed with no follows uses normal recency order', function () {
    $user  = User::factory()->create();
    $older = ClothingItem::factory()->create(['status' => 'available', 'created_at' => now()->subHour()]);
    $newer = ClothingItem::factory()->create(['status' => 'available', 'created_at' => now()]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard'));
    $response->assertOk();

    $ids      = collect($response->json('data'))->pluck('id')->values()->toArray();
    $olderPos = array_search($older->id, $ids);
    $newerPos = array_search($newer->id, $ids);

    expect($newerPos)->toBeLessThan($olderPos);
});

test('for-you feed boosts followed users within tag-matched results', function () {
    $tag     = \App\Models\CiTags::inRandomOrder()->first();
    $user    = User::factory()->create();
    $followed = User::factory()->create();
    $other    = User::factory()->create();

    // Item user liked to build the tag pool
    $likedItem = ClothingItem::factory()->create(['user_id' => $other->id, 'status' => 'available']);
    $likedItem->tags()->sync([$tag->id]);
    $user->likes()->attach($likedItem->id);

    // Followed user's matching item (older)
    $followedItem = ClothingItem::factory()->create([
        'user_id'    => $followed->id,
        'status'     => 'available',
        'created_at' => now()->subMinutes(10),
    ]);
    $followedItem->tags()->sync([$tag->id]);

    // Non-followed user's matching item (newer)
    $otherItem = ClothingItem::factory()->create([
        'user_id'    => $other->id,
        'status'     => 'available',
        'created_at' => now()->subMinutes(1),
    ]);
    $otherItem->tags()->sync([$tag->id]);

    $user->followings()->attach($followed->id);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard', ['sort' => 'for-you']));
    $response->assertOk();

    $ids         = collect($response->json('data'))->pluck('id')->values()->toArray();
    $followedPos = array_search($followedItem->id, $ids);
    $otherPos    = array_search($otherItem->id, $ids);

    expect($followedPos)->toBeLessThan($otherPos);
});

test('for-you feed with no likes boosts followed users but still shows global feed', function () {
    $user     = User::factory()->create();
    $followed  = User::factory()->create();
    $stranger  = User::factory()->create();

    // Followed user's item is older; stranger's is newer — followed should still rank first
    $followedItem = ClothingItem::factory()->create([
        'user_id'    => $followed->id,
        'status'     => 'available',
        'created_at' => now()->subMinutes(10),
    ]);
    $strangerItem = ClothingItem::factory()->create([
        'user_id'    => $stranger->id,
        'status'     => 'available',
        'created_at' => now()->subMinutes(1),
    ]);

    $user->followings()->attach($followed->id);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard', ['sort' => 'for-you']));
    $response->assertOk();

    $ids         = collect($response->json('data'))->pluck('id')->values()->toArray();
    $followedPos = array_search($followedItem->id, $ids);
    $strangerPos = array_search($strangerItem->id, $ids);

    // Both appear, but followed user ranks first despite older timestamp
    $this->assertNotFalse($followedPos);
    $this->assertNotFalse($strangerPos);
    expect($followedPos)->toBeLessThan($strangerPos);
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
