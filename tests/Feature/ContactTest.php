<?php

use App\Models\User;

test('guest can submit a contact message', function () {
    $response = $this->postJson('/api/contact', [
        'name'    => 'Jane Guest',
        'email'   => 'jane@example.com',
        'subject' => 'Hello',
        'message' => 'I have a question.',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('contact_messages', [
        'email'   => 'jane@example.com',
        'user_id' => null,
    ]);
});

test('authenticated user contact message stores user_id', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/contact', [
        'name'    => 'Jane Member',
        'email'   => 'jane@example.com',
        'subject' => 'Hello',
        'message' => 'I have a question.',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('contact_messages', [
        'email'   => 'jane@example.com',
        'user_id' => $user->id,
    ]);
});

test('contact form requires name', function () {
    $this->postJson('/api/contact', [
        'email'   => 'jane@example.com',
        'subject' => 'Hello',
        'message' => 'Hi.',
    ])->assertStatus(422)->assertJsonValidationErrors('name');
});

test('contact form requires valid email', function () {
    $this->postJson('/api/contact', [
        'name'    => 'Jane',
        'email'   => 'not-an-email',
        'subject' => 'Hello',
        'message' => 'Hi.',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

test('contact form requires subject', function () {
    $this->postJson('/api/contact', [
        'name'    => 'Jane',
        'email'   => 'jane@example.com',
        'message' => 'Hi.',
    ])->assertStatus(422)->assertJsonValidationErrors('subject');
});

test('contact form requires message', function () {
    $this->postJson('/api/contact', [
        'name'    => 'Jane',
        'email'   => 'jane@example.com',
        'subject' => 'Hello',
    ])->assertStatus(422)->assertJsonValidationErrors('message');
});

test('contact message max 2000 chars enforced', function () {
    $this->postJson('/api/contact', [
        'name'    => 'Jane',
        'email'   => 'jane@example.com',
        'subject' => 'Hello',
        'message' => str_repeat('a', 2001),
    ])->assertStatus(422)->assertJsonValidationErrors('message');
});
