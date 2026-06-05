<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('unauthenticated users cannot upload an avatar', function () {
    Storage::fake('s3');
    $this->postJson(route('profile.avatar'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertStatus(401);
});

test('unverified users cannot upload an avatar', function () {
    Storage::fake('s3');
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->postJson(route('profile.avatar'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertStatus(403);
});

test('a user can upload an avatar', function () {
    Storage::fake('s3');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('profile.avatar'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $response->assertOk()->assertJsonStructure(['avatar_url']);

    $user->refresh();
    expect($user->avatar_path)->not->toBeNull();
    Storage::disk('s3')->assertExists($user->avatar_path);
});

test('avatar upload rejects invalid file types', function () {
    Storage::fake('s3');
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('profile.avatar'), [
        'avatar' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
    ])->assertStatus(422)->assertJsonValidationErrors(['avatar']);
});

test('avatar upload rejects files over 2MB', function () {
    Storage::fake('s3');
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('profile.avatar'), [
        'avatar' => UploadedFile::fake()->image('big.jpg')->size(3000),
    ])->assertStatus(422)->assertJsonValidationErrors(['avatar']);
});

test('uploading a new avatar deletes the old one from S3', function () {
    Storage::fake('s3');
    $user = User::factory()->create();

    // Upload first avatar
    $this->actingAs($user)->postJson(route('profile.avatar'), [
        'avatar' => UploadedFile::fake()->image('first.jpg'),
    ]);

    $user->refresh();
    $oldPath = $user->avatar_path;
    Storage::disk('s3')->assertExists($oldPath);

    // Upload second avatar
    $this->actingAs($user)->postJson(route('profile.avatar'), [
        'avatar' => UploadedFile::fake()->image('second.jpg'),
    ]);

    Storage::disk('s3')->assertMissing($oldPath);
    $user->refresh();
    expect($user->avatar_path)->not->toBe($oldPath);
});
