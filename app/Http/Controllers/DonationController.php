<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class DonationController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'amount_cents' => 'required|integer|min:200|max:1000000',
        ]);

        Stripe::setApiKey(config('services.stripe.secret'));

        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        $donation = Donation::create([
            'stripe_session_id' => 'pending_' . uniqid(),
            'amount_cents'      => $request->amount_cents,
            'currency'          => 'cad',
            'status'            => 'pending',
        ]);

        $session = StripeSession::create([
            'mode'                  => 'payment',
            'payment_method_types'  => ['card'],
            'line_items'            => [[
                'price_data' => [
                    'currency'     => 'cad',
                    'unit_amount'  => $request->amount_cents,
                    'product_data' => [
                        'name' => 'Donation to Closet Swap',
                    ],
                ],
                'quantity' => 1,
            ]],
            'success_url' => $frontendUrl . '/donate/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $frontendUrl . '/donate/cancel',
            'metadata'    => ['donation_id' => $donation->id],
        ]);

        // Update the pending record with the real session ID
        $donation->update(['stripe_session_id' => $session->id]);

        return response()->json(['url' => $session->url]);
    }

    public function webhook(Request $request): Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature.', 400);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload.', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            Donation::where('stripe_session_id', $session->id)->update([
                'status'      => 'completed',
                'donor_email' => $session->customer_details?->email,
            ]);
        }

        return response('OK', 200);
    }
}
