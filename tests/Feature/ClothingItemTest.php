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

test('a user can create a clothing item with units', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $unit = CiUnit::first();

    $response = $this->actingAs($user)->postJson(route('items.store'), array_merge(validItemPayload(), [
        'units' => $unit->id,
    ]));

    $response->assertStatus(201);
    $this->assertDatabaseHas('clothing_items', [
        'title'        => 'Test Jacket',
        'user_id'      => $user->id,
        'ci_units_id'  => $unit->id,
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

    // Missing condition → 422, no DB change
    $response = $this->actingAs($user)->postJson(route('items.store'), array_diff_key(validItemPayload(), ['condition' => '']));
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['condition']);
    $this->assertDatabaseCount('clothing_items', $itemCount);
});

test('a user can create a clothing item with colors, materials, and tags', function () {
    Storage::fake('s3');
    $user     = User::factory()->create();
    $color    = \App\Models\CiColors::first();
    $material = \App\Models\CiMaterial::first();
    $tag      = \App\Models\CiTags::first();

    $response = $this->actingAs($user)->postJson(route('items.store'), array_merge(validItemPayload(), [
        'colors'    => [$color->name],
        'materials' => [$material->name],
        'tags'      => [$tag->name],
    ]));

    $response->assertStatus(201);
    $item = \App\Models\ClothingItem::find($response->json('id'));
    expect($item->colors->pluck('name')->toArray())->toContain($color->name);
    expect($item->materials->pluck('name')->toArray())->toContain($material->name);
    expect($item->tags->pluck('name')->toArray())->toContain($tag->name);
});

test('a user can update a clothing item', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $item = \App\Models\ClothingItem::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->patchJson(route('items.update', $item), [
        'title' => 'Updated Title',
        'brand' => 'Foobar',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('clothing_items', [
        'id'    => $item->id,
        'title' => 'Updated Title',
        'brand' => 'Foobar',
    ]);
});

test('a user can not update a clothing item they do not own', function () {
    Storage::fake('s3');

    $owner = User::factory()->create();
    $other = User::factory()->create();
    $item  = \App\Models\ClothingItem::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other)->patchJson(route('items.update', $item), [
        'title' => 'Hacked Title',
    ]);

    $response->assertStatus(403);
    $this->assertDatabaseMissing('clothing_items', ['title' => 'Hacked Title']);
});

test('a user can delete a clothing item', function () {
    Storage::fake('s3');

    $user = User::factory()->create();
    $item = \App\Models\ClothingItem::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->deleteJson(route('items.destroy', $item));

    $response->assertStatus(200);
    $this->assertDatabaseMissing('clothing_items', ['id' => $item->id]);
});

test('a user can not delete a clothing item they do not own', function () {
    Storage::fake('s3');

    $owner = User::factory()->create();
    $other = User::factory()->create();
    $item  = \App\Models\ClothingItem::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other)->deleteJson(route('items.destroy', $item));

    $response->assertStatus(403);
    $this->assertDatabaseHas('clothing_items', ['id' => $item->id]);
});

test('an authenticated user can view a clothing item', function () {
    Storage::fake('s3');
    $user = User::factory()->create();
    $item = \App\Models\ClothingItem::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson(route('items.show', $item));
    $response->assertStatus(200)
             ->assertJsonStructure(['item' => ['id', 'title'], 'images']);
});

test('a user can mark a clothing item as taken', function () {

});

test('a user can not mark a clothing item as taken that they do not own', function () {

});

test('a user can start a chat for a clothing item', function () {

});
