<?php

use App\Models\User;
use App\Models\WantedAd;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('a user can browse wanted ads', function () {
    $ads = WantedAd::factory(3)->create();
    $response = $this->getJson(route('wanted'));
    $response->assertOk();
    $ids = collect($response->json())->pluck('id');
    foreach ($ads->pluck('id') as $id) {
        expect($ids)->toContain($id);
    }
});

test('a user can browse wanted ads by category', function () {
    $excluded = WantedAd::factory(3)->create(['category' => WantedAd::CATEGORIES[0]]);
    $included  = WantedAd::factory(3)->create(['category' => WantedAd::CATEGORIES[1]]);
    $response  = $this->getJson(route('wanted', ['filters' => ['category' => WantedAd::CATEGORIES[1]]]));
    $response->assertOk();
    $ids = collect($response->json())->pluck('id');
    foreach ($included->pluck('id') as $id) {
        expect($ids)->toContain($id);
    }
    foreach ($excluded->pluck('id') as $id) {
        expect($ids)->not->toContain($id);
    }
});

test('a user cannot see ads for someone who has blocked them', function () {
    $blocker = User::factory()->create();
    $ad = WantedAd::factory()->create(['user_id' => $blocker->id]);
    $this->actingAs($blocker);
    $this->user->block();
    $this->actingAs($this->user);
    $response = $this->getJson(route('wanted'));
    $response->assertOk();
    $ids = collect($response->json())->pluck('id');
    expect($ids)->not->toContain($ad->id);
});

test('a user cannot see ads for someone who they have blocked', function () {
    $blockedUser = User::factory()->create();
    $ad = WantedAd::factory()->create(['user_id' => $blockedUser->id]);
    $blockedUser->block();
    $response = $this->getJson(route('wanted'));
    $response->assertOk();
    $ids = collect($response->json())->pluck('id');
    expect($ids)->not->toContain($ad->id);
});

test('a user can create a wanted ad', function () {
    $title = time() . 'Looking for a new hat';
    $ad = WantedAd::factory()->make(['title' => $title]);
    unset($ad->user_id);
    $response = $this->post(route('wanted.store'), $ad->toArray());
    $this->assertDatabaseHas('wanted_ads', ['title' => $title]);
});

test('a user can update a wanted ad', function () {
    $ad = WantedAd::factory()->create(['user_id' => $this->user->id]);
    $new_title = time() . 'Looking for a new hat';
    $response = $this->post(route('wanted.update', $ad->id), [
        'title' => $new_title,
        'description' => $ad->description,
        'category' => $ad->category,
    ]);
    $this->assertDatabaseHas('wanted_ads', ['title' => $new_title]);
});

test('a user can not update a wanted ad they do not own', function () {
    $ad = WantedAd::factory()->create();
    $new_title = time() . 'Looking for a new hat';
    $response = $this->post(route('wanted.update', $ad->id), [
        'title' => $new_title,
        'description' => $ad->description,
        'category' => $ad->category,
    ]);
    $this->assertDatabaseMissing('wanted_ads', ['title' => $new_title]);
});

test('a user can delete a wanted ad', function () {
    $ad = WantedAd::factory()->create(['user_id' => $this->user->id]);
    $response = $this->delete(route('wanted.destroy', $ad->id));
    $this->assertDatabaseMissing('wanted_ads', ['id' => $ad->id]);
});

test('a user can not delete a wanted ad they do not own', function () {
    $ad = WantedAd::factory()->create();
    $response = $this->delete(route('wanted.destroy', $ad->id));
    $this->assertDatabaseHas('wanted_ads', ['id' => $ad->id]);
});
