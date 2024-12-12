<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// test a clothing item knows its images
test('a clothing item knows its images', function () {
    $item = \App\Models\ClothingItem::factory()->create();
    $image = \App\Models\ClothingItemImage::factory()->create(['clothing_item_id' => $item->id]);
    $this->assertEquals($image->clothing_item_id, $item->images->first()->clothing_item_id);
});

// test a clothing item image belongs to a clothing item
test('a clothing item image belongs to a clothing item', function () {
    $item = \App\Models\ClothingItem::factory()->create();
    $image = \App\Models\ClothingItemImage::factory()->create(['clothing_item_id' => $item->id]);
    $this->assertEquals($item->id, $image->clothing_item_id);
});

// test a clothing item knows its user
test('a clothing item knows its user', function () {
    $user = \App\Models\User::factory()->create();
    $item = \App\Models\ClothingItem::factory()->create(['user_id' => $user->id]);
    $this->assertEquals($user->id, $item->user->id);
});

// test a user has many clothing items
test('a user has many clothing items', function () {
    $user = \App\Models\User::factory()->create();
    $item = \App\Models\ClothingItem::factory()->create(['user_id' => $user->id]);
    $this->assertEquals($user->clothingItems->first()->id, $item->id);
});
