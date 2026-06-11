<?php

namespace App\Services;

use App\Models\Donation;

/**
 * Handles donation-specific business logic.
 *
 * Delegates all Stripe SDK calls to StripeService.
 * To add subscriptions, create a SubscriptionService that
 * follows the same pattern — inject StripeService, own the
 * domain logic, register event handlers in DonationController.
 */
class DonationService
{
    public function __construct(private StripeService $stripe) {}

    /**
     * Create a Stripe Checkout Session for a one-off donation.
     * Persists a pending Donation record and returns the checkout URL.
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

        $session = $this->stripe->createCheckoutSession([
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
     * Handle a verified checkout.session.completed event for a donation.
     */
    public function handleSessionCompleted(object $session): void
    {
        Donation::where('stripe_session_id', $session->id)->update([
            'status'      => 'completed',
            'donor_email' => $session->customer_details?->email,
        ]);
    }
}
