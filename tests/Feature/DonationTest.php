<?php

namespace Tests\Feature;

use App\Models\Donation;
use Illuminate\Support\Facades\Config;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Event as StripeEvent;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

// ── checkout ──────────────────────────────────────────────────────────────────

it('checkout returns a stripe url for a valid amount', function () {
    $fakeSession = (object) ['id' => 'cs_test_abc123', 'url' => 'https://checkout.stripe.com/pay/cs_test_abc123'];

    $mock = \Mockery::mock('alias:' . StripeSession::class);
    $mock->shouldReceive('create')->once()->andReturn($fakeSession);

    $response = $this->postJson('/api/donations/checkout', ['amount_cents' => 1000]);

    $response->assertStatus(200)->assertJsonStructure(['url']);
    $this->assertDatabaseHas('donations', ['stripe_session_id' => 'cs_test_abc123', 'amount_cents' => 1000]);
});

it('checkout rejects amounts below the minimum', function () {
    $response = $this->postJson('/api/donations/checkout', ['amount_cents' => 100]);
    $response->assertStatus(422)->assertJsonValidationErrors(['amount_cents']);
});

it('checkout rejects a missing amount', function () {
    $response = $this->postJson('/api/donations/checkout', []);
    $response->assertStatus(422)->assertJsonValidationErrors(['amount_cents']);
});

it('checkout rejects a non-integer amount', function () {
    $response = $this->postJson('/api/donations/checkout', ['amount_cents' => 'ten']);
    $response->assertStatus(422)->assertJsonValidationErrors(['amount_cents']);
});

// ── webhook ───────────────────────────────────────────────────────────────────

it('webhook rejects an invalid signature', function () {
    Config::set('services.stripe.webhook_secret', 'whsec_test');

    $mock = \Mockery::mock('alias:' . Webhook::class);
    $mock->shouldReceive('constructEvent')
        ->once()
        ->andThrow(new SignatureVerificationException('bad sig', null));

    $response = $this->postJson('/api/donations/webhook', [], ['Stripe-Signature' => 'bad']);
    $response->assertStatus(400);
});

it('webhook marks a donation completed on checkout.session.completed', function () {
    Config::set('services.stripe.webhook_secret', 'whsec_test');

    $donation = Donation::create([
        'stripe_session_id' => 'cs_test_xyz',
        'amount_cents'      => 2000,
        'currency'          => 'cad',
        'status'            => 'pending',
    ]);

    $sessionObj = (object) [
        'id'               => 'cs_test_xyz',
        'customer_details' => (object) ['email' => 'donor@example.com'],
    ];

    $event = (object) [
        'type' => 'checkout.session.completed',
        'data' => (object) ['object' => $sessionObj],
    ];

    $mock = \Mockery::mock('alias:' . Webhook::class);
    $mock->shouldReceive('constructEvent')->once()->andReturn($event);

    $response = $this->postJson('/api/donations/webhook', [], ['Stripe-Signature' => 'valid']);
    $response->assertStatus(200);

    $this->assertDatabaseHas('donations', [
        'id'          => $donation->id,
        'status'      => 'completed',
        'donor_email' => 'donor@example.com',
    ]);
});
