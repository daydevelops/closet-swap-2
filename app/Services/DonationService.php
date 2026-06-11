<?php

namespace App\Services;

use App\Models\Donation;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class DonationService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Checkout Session and a pending Donation record.
     * Returns the Stripe-hosted checkout URL.
     */
    public function createCheckoutSession(int $amountCents): string
    {
        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        $donation = Donation::create([
            'stripe_session_id' => 'pending_' . uniqid(),
            'amount_cents'      => $amountCents,
            'currency'          => 'cad',
            'status'            => 'pending',
        ]);

        $session = StripeSession::create([
            'mode'                 => 'payment',
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'cad',
                    'unit_amount'  => $amountCents,
                    'product_data' => ['name' => 'Donation to Closet Swap'],
                ],
                'quantity' => 1,
            ]],
            'success_url' => $frontendUrl . '/donate/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $frontendUrl . '/donate/cancel',
            'metadata'    => ['donation_id' => $donation->id],
        ]);

        $donation->update(['stripe_session_id' => $session->id]);

        return $session->url;
    }

    /**
     * Parse and verify an incoming Stripe webhook payload.
     * Returns the verified event object.
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

    /**
     * Handle a verified checkout.session.completed event.
     */
    public function handleSessionCompleted(object $session): void
    {
        Donation::where('stripe_session_id', $session->id)->update([
            'status'      => 'completed',
            'donor_email' => $session->customer_details?->email,
        ]);
    }
}
