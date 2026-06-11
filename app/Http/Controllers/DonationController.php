<?php

namespace App\Http\Controllers;

use App\Services\DonationService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;

class DonationController extends Controller
{
    public function __construct(
        private StripeService  $stripe,
        private DonationService $donations,
    ) {}

    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'amount_cents' => 'required|integer|min:200|max:1000000',
        ]);

        $url = $this->donations->createCheckoutSession($request->amount_cents);

        return response()->json(['url' => $url]);
    }

    public function webhook(Request $request): Response
    {
        try {
            $event = $this->stripe->constructWebhookEvent(
                $request->getContent(),
                $request->header('Stripe-Signature')
            );
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature.', 400);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload.', 400);
        }

        // Map Stripe event types to handler callbacks.
        // To add subscriptions, inject SubscriptionService and
        // register its handlers here in the same pattern.
        $handlers = [
            'checkout.session.completed' => fn($data) => $this->donations->handleSessionCompleted($data),
            // 'customer.subscription.created'   => fn($data) => $this->subscriptions->handleCreated($data),
            // 'customer.subscription.deleted'   => fn($data) => $this->subscriptions->handleCancelled($data),
            // 'invoice.payment_succeeded'       => fn($data) => $this->subscriptions->handleRenewal($data),
        ];

        if (isset($handlers[$event->type])) {
            $handlers[$event->type]($event->data->object);
        }

        return response('OK', 200);
    }
}
