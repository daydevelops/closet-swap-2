<?php

use App\Models\ClothingItem;
use App\Models\ClothingItemImage;
use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

// --- Access control ---

test('unauthenticated users cannot access admin user list', function () {
    $this->getJson('/api/admin/users')->assertStatus(401);
});

test('non-admin users get 403 on admin user list', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->getJson('/api/admin/users')->assertStatus(403);
});

test('non-admin users get 403 on admin user show', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($user)->getJson("/api/admin/users/{$other->id}")->assertStatus(403);
});

test('non-admin users get 403 on admin user delete', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($user)
        ->deleteJson("/api/admin/users/{$other->id}", ['reason' => 'test'])
        ->assertStatus(403);
});

// --- User list ---

test('admin can list users with pagination', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(3)->create();

    $response = $this->actingAs($admin)->getJson('/api/admin/users');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'email', 'created_at', 'email_verified_at', 'item_count', 'is_admin']],
            'current_page',
            'total',
        ]);
});

test('admin can search users by name', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create(['name' => 'UniqueNameXYZ']);
    User::factory()->create(['name' => 'Someone Else']);

    $response = $this->actingAs($admin)->getJson('/api/admin/users?search=UniqueNameXYZ');

    $response->assertOk();
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['name'])->toBe('UniqueNameXYZ');
});

test('admin can search users by email', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'find-me@example.com']);
    User::factory()->create(['email' => 'other@example.com']);

    $response = $this->actingAs($admin)->getJson('/api/admin/users?search=find-me');

    $response->assertOk();
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['email'])->toBe('find-me@example.com');
});

// --- User show ---

test('admin can view user detail', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['bio' => 'Hello world', 'contact_handle' => '@handle']);

    $response = $this->actingAs($admin)->getJson("/api/admin/users/{$user->id}");

    $response->assertOk()
        ->assertJsonFragment(['bio' => 'Hello world', 'contact_handle' => '@handle']);
});

// --- User delete ---

test('admin can delete a user with a reason', function () {
    Storage::fake('s3');
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $item = ClothingItem::factory()->create(['user_id' => $user->id]);
    $image = ClothingItemImage::factory()->create([
        'clothing_item_id' => $item->id,
        'path' => 'images/test.jpg',
    ]);

    Storage::disk('s3')->put('images/test.jpg', 'fake');

    $response = $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$user->id}", ['reason' => 'Violated community guidelines.']);

    $response->assertOk();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertDatabaseMissing('clothing_items', ['id' => $item->id]);
    $this->assertDatabaseMissing('clothing_item_images', ['id' => $image->id]);

    Storage::disk('s3')->assertMissing('images/test.jpg');

    Notification::assertSentTo($user, AccountDeletedNotification::class, function ($n) {
        return $n->reason === 'Violated community guidelines.';
    });
});

test('admin cannot delete themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$admin->id}", ['reason' => 'Self delete.'])
        ->assertStatus(422);

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('delete without reason returns 422', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$user->id}")
        ->assertStatus(422);
});
