<?php

use Illuminate\Support\Facades\Cache;

test('item options endpoint returns all expected keys', function () {
    $response = $this->get(route('items.create'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'types',
            'sizes',
            'shoe_sizes',
            'fits',
            'conditions',
            'units',
            'genders',
            'tags',
            'colors',
            'materials',
        ]);
});

test('item options are cached after the first request', function () {
    Cache::forget('item_options');

    $this->get(route('items.create'));

    expect(Cache::has('item_options'))->toBeTrue();
});
