<?php

use App\Models\CiCondition;
use App\Models\CiFit;
use App\Models\CiGender;
use App\Models\CiSize;
use App\Models\CiType;
use App\Models\CiUnit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Returns a valid item payload using the first seeded option from each lookup table.
 * Tests that need to omit or override specific fields can use array_diff_key / array_merge.
 */
function validItemPayload(): array
{
    return [
        'title'       => 'Test Jacket',
        'description' => 'A great jacket in excellent condition.',
        'type'        => CiType::first()->id,
        'gender'      => CiGender::first()->id,
        'size'        => CiSize::first()->id,
        'units'       => CiUnit::first()->id,
        'fit'         => CiFit::first()->id,
        'condition'   => CiCondition::first()->id,
        'brand'       => 'Zara',
        'pictures'    => [UploadedFile::fake()->image('jacket.jpg')],
    ];
}

test('a user can upload images for a clothing item', function () {

});

test('a user can create a clothing item', function () {
    Storage::fake('s3');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('items.store'), validItemPayload());

    $response->assertStatus(201);
    $this->assertDatabaseHas('clothing_items', [
        'title'   => 'Test Jacket',
        'brand'   => 'Zara',
        'user_id' => $user->id,
    ]);
});

test('a user cannot create a clothing item with missing required fields', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $itemCount = \App\Models\ClothingItem::count();

    // Missing gender → 422, no DB change
    $response = $this->actingAs($user)->postJson(route('items.store'), array_diff_key(validItemPayload(), ['gender' => '']));
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['gender']);
    $this->assertDatabaseCount('clothing_items', $itemCount);

    // Missing brand → 422, no DB change
    $response = $this->actingAs($user)->postJson(route('items.store'), array_diff_key(validItemPayload(), ['brand' => '']));
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['brand']);
    $this->assertDatabaseCount('clothing_items', $itemCount);
});

test('a user can update a clothing item', function () {

});

test('a user can not update a clothing item they do not own', function () {

});

test('a user can delete a clothing item', function () {

});

test('a user can not delete a clothing item they do not own', function () {

});

test('a user can mark a clothing item as taken', function () {

});

test('a user can not mark a clothing item as taken that they do not own', function () {

});

test('a user can start a chat for a clothing item', function () {

});
