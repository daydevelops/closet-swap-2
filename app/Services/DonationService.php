<?php

namespace App\Services;

use App\Models\Donation;

/**
 * Handles donation-specific business logic.
 *
 * Delegates all Stripe SDK calls to StripeService.
 * To add subscriptions, create a SubscriptionService that
 * follows the same pattern — inject StripeService, own the
 * domain logic, register event handlers in HandleStripeWebhookEvent.
 */
class DonationService
{
    public function __construct(private StripeService $stripe) {}

    /**
     * Create a Stripe Checkout Session for a one-off donation.
     * Calls Stripe first, then persists the Donation record in one step.
     *
     * @param  string $ip  Caller's IP address, used to generate the idempotency key.
     */
    public function createCheckoutSession(int $amountCents, string $ip): string
    {
        $currency    = config('services.stripe.currency');
        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        // Idempotency key: deduplicates retries of the same amount from the
        // same IP within the same minute (e.g. double-click on Donate).
        $idempotencyKey = hash('sha256', implode('|', [
            $ip,
            $amountCents,
            now()->startOfMinute()->timestamp,
        ]));

        $session = $this->stripe->createCheckoutSession([
            'mode'                 => 'payment',
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => $currency,
                    'unit_amount'  => $amountCents,
                    'product_data' => ['name' => 'Donation to Closet Swap'],
                ],
                'quantity' => 1,
            ]],
            'success_url' => $frontendUrl . '/donate/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $frontendUrl . '/donate/cancel',
        ], $idempotencyKey);

        Donation::create([
            'stripe_session_id' => $session->id,
            'amount_cents'      => $amountCents,
            'currency'          => $currency,
            'status'            => 'pending',
        ]);

        return $session->url;
    }

    /**
     * Handle a verified checkout.session.completed event for a donation.
     * The where('status', 'pending') guard makes this idempotent —
     * duplicate webhook deliveries from Stripe are a no-op.
     */
    public function handleSessionCompleted(object $session): void
    {
        Donation::where('stripe_session_id', $session->id)
            ->where('status', 'pending')
            ->update([
                'status'      => 'completed',
                'donor_email' => $session->customer_details?->email ?? null,
            ]);
    }
}
