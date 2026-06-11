<?php

namespace Tests\Feature;

use App\Jobs\HandleStripeWebhookEvent;
use App\Models\Donation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

// ── checkout ──────────────────────────────────────────────────────────────────

it('checkout returns a stripe url for a valid amount', function () {
    $fakeSession = (object) ['id' => 'cs_test_abc123', 'url' => 'https://checkout.stripe.com/pay/cs_test_abc123'];

    $mock = \Mockery::mock('alias:' . StripeSession::class);
    $mock->shouldReceive('create')->once()->andReturn($fakeSession);

    $response = $this->postJson('/api/donations/checkout', ['amount_cents' => 1000]);

    $response->assertStatus(200)->assertJsonStructure(['url']);
    $this->assertDatabaseHas('donations', [
        'stripe_session_id' => 'cs_test_abc123',
        'amount_cents'      => 1000,
        'status'            => 'pending',
    ]);
});

it('checkout returns 503 when stripe api throws', function () {
    $mock = \Mockery::mock('alias:' . StripeSession::class);
    $mock->shouldReceive('create')->once()->andThrow(
        \Mockery::mock(ApiErrorException::class)
    );

    $response = $this->postJson('/api/donations/checkout', ['amount_cents' => 1000]);

    $response->assertStatus(503)->assertJsonFragment(['message' => 'Payment service unavailable. Please try again.']);
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

it('webhook dispatches a job and returns 200 immediately', function () {
    Queue::fake();
    Config::set('services.stripe.webhook_secret', 'whsec_test');

    $sessionObj = (object) [
        'id'               => 'cs_test_dispatch',
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
    Queue::assertPushed(HandleStripeWebhookEvent::class, function ($job) {
        return $job->eventType === 'checkout.session.completed';
    });
});

it('webhook job marks a donation completed', function () {
    $donation = Donation::create([
        'stripe_session_id' => 'cs_test_xyz',
        'amount_cents'      => 2000,
        'currency'          => 'cad',
        'status'            => 'pending',
    ]);

    $job = new HandleStripeWebhookEvent('checkout.session.completed', [
        'id'               => 'cs_test_xyz',
        'customer_details' => ['email' => 'donor@example.com'],
    ]);

    $job->handle(app(\App\Services\DonationService::class));

    $this->assertDatabaseHas('donations', [
        'id'          => $donation->id,
        'status'      => 'completed',
        'donor_email' => 'donor@example.com',
    ]);
});

it('duplicate webhook event on completed donation is a no-op', function () {
    $donation = Donation::create([
        'stripe_session_id' => 'cs_test_dupe',
        'amount_cents'      => 500,
        'currency'          => 'cad',
        'status'            => 'completed',
        'donor_email'       => 'original@example.com',
    ]);

    $job = new HandleStripeWebhookEvent('checkout.session.completed', [
        'id'               => 'cs_test_dupe',
        'customer_details' => ['email' => 'different@example.com'],
    ]);

    $job->handle(app(\App\Services\DonationService::class));

    // Email must not be overwritten
    $this->assertDatabaseHas('donations', [
        'id'          => $donation->id,
        'status'      => 'completed',
        'donor_email' => 'original@example.com',
    ]);
});

// ── expire stale command ───────────────────────────────────────────────────────

it('expire-stale command marks old pending donations as expired', function () {
    $stale = Donation::create([
        'stripe_session_id' => 'cs_stale',
        'amount_cents'      => 1000,
        'currency'          => 'cad',
        'status'            => 'pending',
    ]);
    $stale->created_at = now()->subHours(25);
    $stale->save();

    $recent = Donation::create([
        'stripe_session_id' => 'cs_recent',
        'amount_cents'      => 1000,
        'currency'          => 'cad',
        'status'            => 'pending',
    ]);

    $completed = Donation::create([
        'stripe_session_id' => 'cs_done',
        'amount_cents'      => 1000,
        'currency'          => 'cad',
        'status'            => 'completed',
    ]);
    $completed->created_at = now()->subHours(48);
    $completed->save();

    $this->artisan('donations:expire-stale')->assertSuccessful();

    $this->assertDatabaseHas('donations', ['id' => $stale->id,     'status' => 'expired']);
    $this->assertDatabaseHas('donations', ['id' => $recent->id,    'status' => 'pending']);
    $this->assertDatabaseHas('donations', ['id' => $completed->id, 'status' => 'completed']);
});
