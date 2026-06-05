<?php

use App\Models\ClothingItem;
use App\Models\User;

// --- Item detail page (behind auth:sanctum + verified middleware) ---

test('verified user can see contact handle on item detail', function () {
    $seller = User::factory()->create(['contact_handle' => '@seller_signal']);
    $viewer = User::factory()->create(['email_verified_at' => now()]);
    $item   = ClothingItem::factory()->create(['user_id' => $seller->id]);

    $this->actingAs($viewer);
    $response = $this->getJson(route('items.show', $item));
    $response->assertOk();
    $this->assertEquals('@seller_signal', $response->json('item.user.contact_handle'));
});

test('unverified user cannot access item detail', function () {
    $seller = User::factory()->create(['contact_handle' => '@seller_signal']);
    $viewer = User::factory()->create(['email_verified_at' => null]);
    $item   = ClothingItem::factory()->create(['user_id' => $seller->id]);

    $this->actingAs($viewer);
    $response = $this->getJson(route('items.show', $item));
    $response->assertStatus(403);
});

test('guest cannot access item detail', function () {
    $seller = User::factory()->create(['contact_handle' => '@seller_signal']);
    $item   = ClothingItem::factory()->create(['user_id' => $seller->id]);

    $response = $this->getJson(route('items.show', $item));
    $response->assertStatus(401);
});

// --- Profile page (behind auth:sanctum + verified middleware) ---

test('verified user can see contact handle on profile', function () {
    $profileUser = User::factory()->create(['contact_handle' => '@profile_handle']);
    $viewer      = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($viewer);
    $response = $this->getJson(route('profile.show', $profileUser));
    $response->assertOk();
    $this->assertEquals('@profile_handle', $response->json('contact_handle'));
});

test('unverified user cannot access profile', function () {
    $profileUser = User::factory()->create(['contact_handle' => '@profile_handle']);
    $viewer      = User::factory()->create(['email_verified_at' => null]);

    $this->actingAs($viewer);
    $response = $this->getJson(route('profile.show', $profileUser));
    $response->assertStatus(403);
});

test('guest cannot access profile', function () {
    $profileUser = User::factory()->create(['contact_handle' => '@profile_handle']);

    $response = $this->getJson(route('profile.show', $profileUser));
    $response->assertStatus(401);
});
