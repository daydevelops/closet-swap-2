<?php

namespace App\Jobs;

use App\Services\DonationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Processes a verified Stripe webhook event asynchronously.
 *
 * The controller dispatches this job and returns 200 immediately,
 * keeping webhook response times well within Stripe's timeout window.
 *
 * To handle subscription events, inject SubscriptionService and add
 * entries to the $handlers map below.
 */
class HandleStripeWebhookEvent implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $eventType,
        public readonly array  $eventData,
    ) {}

    public function handle(DonationService $donations): void
    {
        $handlers = [
            // Deep-cast the array back to a nested stdClass object so property
            // access (e.g. $session->customer_details->email) works correctly.
            'checkout.session.completed' => fn($d) => $donations->handleSessionCompleted(
                json_decode(json_encode($d))
            ),
            // 'customer.subscription.created' => fn($d) => $subscriptions->handleCreated((object) $d),
            // 'customer.subscription.deleted' => fn($d) => $subscriptions->handleCancelled((object) $d),
            // 'invoice.payment_succeeded'     => fn($d) => $subscriptions->handleRenewal((object) $d),
        ];

        ($handlers[$this->eventType] ?? fn() => null)($this->eventData);
    }
}
