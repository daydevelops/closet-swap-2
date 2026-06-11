<?php

namespace App\Http\Controllers;

use App\Jobs\HandleStripeWebhookEvent;
use App\Services\DonationService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;

class DonationController extends Controller
{
    public function __construct(
        private StripeService   $stripe,
        private DonationService $donations,
    ) {}

    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'amount_cents' => 'required|integer|min:200|max:1000000',
        ]);

        try {
            $url = $this->donations->createCheckoutSession(
                $request->amount_cents,
                $request->ip()
            );
        } catch (ApiErrorException $e) {
            return response()->json(
                ['message' => 'Payment service unavailable. Please try again.'],
                503
            );
        }

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

        // Dispatch asynchronously so we return 200 immediately.
        // Stripe will retry if it doesn't hear back within a few seconds.
        HandleStripeWebhookEvent::dispatch(
            $event->type,
            json_decode(json_encode($event->data->object), true)
        );

        return response('OK', 200);
    }
}
