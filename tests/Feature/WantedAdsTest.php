<?php

use App\Models\User;
use App\Models\WantedAd;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('a user can browse wanted ads', function () {
    $items = WantedAd::factory(3)->create();
    $response = $this->get(route('wanted'));
    $response->assertInertia(function (Assert $page) use ($items) {
        $page->component('Wanted')
            ->has('feed', 3)
            ->where('feed', $items->toArray());
    });
});

test('a user can browse wanted ads by category', function () {
    $items_excluded = WantedAd::factory(3)->create(['category' => WantedAd::CATEGORIES[0]]);
    $items_included = WantedAd::factory(3)->create(['category' => WantedAd::CATEGORIES[1]]);
    $response = $this->get(route('wanted', ['filters' => ['category' => WantedAd::CATEGORIES[1]]]));
    $response->assertInertia(function (Assert $page) use ($items_included) {
        $page->component('Wanted')
            ->has('feed', 3)
            ->where('feed', $items_included->toArray());
    });
});

test('a user cannot see ads for someone who has blocked them', function () {
    $blocker = User::factory()->create();
    $ad = WantedAd::factory()->create(['user_id' => $blocker->id]);
    $this->actingAs($blocker);
    $this->user->block();
    $this->actingAs($this->user);
    $this->assertDatabaseHas('blocks', ['blocked_id' => $this->user->id, 'blocked_by' => $blocker->id]);
    $response = $this->get(route('wanted'));
    $response->assertInertia(function (Assert $page) {
        $page->component('Wanted')
            ->has('feed', 0);
    });
});

test('a user cannot see ads for someone who they have blocked', function () {
    $user = User::factory()->create();
    $ad = WantedAd::factory()->create(['user_id' => $user->id]);
    $user->block();
    $this->assertDatabaseHas('blocks', ['blocked_id' => $user->id, 'blocked_by' => $this->user->id]);
    $response = $this->get(route('wanted'));
    $response->assertInertia(function (Assert $page) {
        $page->component('Wanted')
            ->has('feed', 0);
    });
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


