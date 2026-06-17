<?php

use App\Models\ClothingItem;
use App\Models\ClothingItemImage;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

// --- Item moderation ---

test('admin can delete any users clothing item', function () {
    Storage::fake('s3');

    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $liker = User::factory()->create();

    $path = 'images/' . \Illuminate\Support\Str::uuid() . '.jpg';
    $item = ClothingItem::factory()->create(['user_id' => $owner->id]);
    $item->likes()->attach($liker->id);
    ClothingItemImage::factory()->create(['clothing_item_id' => $item->id, 'path' => $path]);
    Storage::disk('s3')->put($path, 'fake');

    $this->actingAs($admin)
        ->deleteJson(route('items.destroy', $item->id))
        ->assertOk();

    $this->assertDatabaseMissing('clothing_items', ['id' => $item->id]);
    Storage::disk('s3')->assertMissing($path);
});

test('non-admin cannot delete another users clothing item', function () {
    $user  = User::factory()->create();
    $owner = User::factory()->create();
    $item  = ClothingItem::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($user)
        ->deleteJson(route('items.destroy', $item->id))
        ->assertStatus(403);

    $this->assertDatabaseHas('clothing_items', ['id' => $item->id]);
});

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
    User::factory()->create(['name' => 'UniqueNameXYZ']);
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
    $path = 'images/' . \Illuminate\Support\Str::uuid() . '.jpg';
    $liker = User::factory()->create();
    $item = ClothingItem::factory()->create(['user_id' => $user->id]);
    $item->likes()->attach($liker->id); // ensure likes are cleaned up before item delete
    $image = ClothingItemImage::factory()->create([
        'clothing_item_id' => $item->id,
        'path' => $path,
    ]);

    Storage::disk('s3')->put($path, 'fake');

    $response = $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$user->id}", ['reason' => 'Violated community guidelines.']);

    $response->assertOk();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertDatabaseMissing('clothing_items', ['id' => $item->id]);
    $this->assertDatabaseMissing('clothing_item_images', ['id' => $image->id]);

    Storage::disk('s3')->assertMissing($path);

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

// --- Contact messages ---

test('admin can list contact messages paginated', function () {
    $admin = User::factory()->admin()->create();
    ContactMessage::factory()->count(3)->create();

    $response = $this->actingAs($admin)->getJson('/api/admin/messages');

    $response->assertOk()->assertJsonStructure([
        'data' => [['id', 'name', 'email', 'subject', 'message', 'read_at', 'created_at', 'user_id', 'user_name']],
        'current_page',
        'total',
    ]);
});

test('non-admin gets 403 on messages list', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->getJson('/api/admin/messages')->assertStatus(403);
});

test('admin can mark a message as read', function () {
    $admin = User::factory()->admin()->create();
    $message = ContactMessage::factory()->create(['read_at' => null]);

    $response = $this->actingAs($admin)
        ->patchJson("/api/admin/messages/{$message->id}/read");

    $response->assertOk();
    $this->assertNotNull($response->json('read_at'));
    $this->assertDatabaseMissing('contact_messages', ['id' => $message->id, 'read_at' => null]);
});

test('marking already-read message is idempotent', function () {
    $admin = User::factory()->admin()->create();
    $readAt = now()->subHour();
    $message = ContactMessage::factory()->create(['read_at' => $readAt]);

    $response = $this->actingAs($admin)
        ->patchJson("/api/admin/messages/{$message->id}/read");

    $response->assertOk();
    expect($message->fresh()->read_at->timestamp)->toBe($readAt->timestamp);
});

test('admin messages list includes member tag when user is authenticated', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'Member User']);
    ContactMessage::factory()->create(['user_id' => $user->id, 'name' => 'Member User']);

    $response = $this->actingAs($admin)->getJson('/api/admin/messages');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['user_id'])->toBe($user->id);
    expect($data[0]['user_name'])->toBe('Member User');
});

test('admin messages list shows null user fields for guests', function () {
    $admin = User::factory()->admin()->create();
    ContactMessage::factory()->create(['user_id' => null]);

    $response = $this->actingAs($admin)->getJson('/api/admin/messages');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['user_id'])->toBeNull();
    expect($data[0]['user_name'])->toBeNull();
});
