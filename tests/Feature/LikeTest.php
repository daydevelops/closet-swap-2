<?php

use App\Models\User;
use App\Models\ClothingItem;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('a user can see all users that have liked their clothing item', function () {
    // given I have a clothing item
    $clothingItem = ClothingItem::factory()->create();
    // and some users exist
    $users_liked = User::factory(3)->create();
    $user_not_liked = User::factory()->create();
    // who have liked my item
    $clothingItem->likes()->attach($users_liked->pluck('id'));
    // when I visit the clothing item page
    $response = $this->get(route('likes.item', $clothingItem));
    // then I should see all the users that have liked my item in the response
    $response->assertSee($users_liked[0]->name);
    $response->assertSee($users_liked[1]->name);
    $response->assertSee($users_liked[2]->name);
    $response->assertDontSee($user_not_liked->name);
});

test('a user can see all the items they have liked', function () {
    // given I have liked some clothing items
    $clothingItemsLiked = ClothingItem::factory(3)->create();
    $clothingItemsNotLiked = ClothingItem::factory()->create();
    $this->user->likes()->attach($clothingItemsLiked->pluck('id'));
    // when I visit the likes page
    $response = $this->get(route('likes.mine'));
    // then I should see all the clothing items I have liked
    $response->assertSee($clothingItemsLiked[0]->name);
    $response->assertSee($clothingItemsLiked[1]->name);
    $response->assertSee($clothingItemsLiked[2]->name);
    $response->assertDontSee($clothingItemsNotLiked->name);
});

test('a user can like a clothing item', function () {
    // given I have a clothing item
    $clothingItem = ClothingItem::factory()->create();
    // when I like the clothing item
    $response = $this->post(route('like',$clothingItem));
    // then I should see a message that the clothing item was liked
    $response->assertJson(['message' => 'Clothing item liked']);
    // and the clothing item should be liked
    $this->assertDatabaseHas('likes', [
        'clothing_item_id' => $clothingItem->id,
        'user_id' => $this->user->id,
    ]);
});

test('a user can unlike a clothing item', function () {
    // given I have liked a clothing item
    $clothingItem = ClothingItem::factory()->create();
    $this->user->likes()->attach($clothingItem->id);
    // when I unlike the clothing item
    $response = $this->delete(route('unlike',$clothingItem));
    // then I should see a message that the clothing item was unliked
    $response->assertJson(['message' => 'Clothing item unliked']);
    // and the clothing item should be unliked
    $this->assertDatabaseMissing('likes', [
        'clothing_item_id' => $clothingItem->id,
        'user_id' => $this->user->id,
    ]);
});
