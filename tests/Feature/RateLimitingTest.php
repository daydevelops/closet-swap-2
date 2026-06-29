<?php

test('login is rate limited after 5 attempts', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson(route('login'), ['email' => 'a@b.com', 'password' => 'wrong']);
    }

    $this->postJson(route('login'), ['email' => 'a@b.com', 'password' => 'wrong'])
         ->assertStatus(429);
});

test('register is rate limited after 10 attempts', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->postJson(route('register'), ['email' => "user{$i}@b.com", 'password' => 'x']);
    }

    $this->postJson(route('register'), ['email' => 'extra@b.com', 'password' => 'x'])
         ->assertStatus(429);
});

test('forgot password is rate limited after 5 attempts', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson(route('password.email'), ['email' => 'a@b.com']);
    }

    $this->postJson(route('password.email'), ['email' => 'a@b.com'])
         ->assertStatus(429);
});

test('contact is rate limited after 3 attempts', function () {
    $payload = [
        'name'    => 'Test',
        'email'   => 'a@b.com',
        'subject' => 'Hi',
        'message' => 'Hello there',
    ];

    for ($i = 0; $i < 3; $i++) {
        $this->postJson(route('contact.store'), $payload);
    }

    $this->postJson(route('contact.store'), $payload)
         ->assertStatus(429);
});
