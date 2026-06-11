<?php

namespace App\Services;

use Stripe\Exception\SignatureVerificationException;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Stripe\Webhook;

/**
 * Thin wrapper around the Stripe SDK.
 *
 * Owns API key initialisation and low-level Stripe calls.
 * Domain services (DonationService, SubscriptionService, etc.)
 * inject this class rather than calling Stripe directly.
 */
class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Checkout Session with the given parameters.
     * Callers are responsible for building the full params array.
     */
    public function createCheckoutSession(array $params): object
    {
        return StripeSession::create($params);
    }

    /**
     * Verify and parse an incoming Stripe webhook payload.
     *
     * @throws SignatureVerificationException
     * @throws \UnexpectedValueException
     */
    public function constructWebhookEvent(string $payload, ?string $sigHeader): object
    {
        return Webhook::constructEvent(
            $payload,
            $sigHeader,
            config('services.stripe.webhook_secret')
        );
    }
}
