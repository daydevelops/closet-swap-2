<?php

namespace App\Http\Controllers;

use App\Services\DonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;

class DonationController extends Controller
{
    public function __construct(private DonationService $donations) {}

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
            $event = $this->donations->constructWebhookEvent(
                $request->getContent(),
                $request->header('Stripe-Signature')
            );
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature.', 400);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload.', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $this->donations->handleSessionCompleted($event->data->object);
        }

        return response('OK', 200);
    }
}
