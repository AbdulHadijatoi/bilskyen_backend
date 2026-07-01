<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $this->paymentService->handleStripeWebhook($payload, $signature);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook error', ['error' => $e->getMessage()]);

            return response('Webhook error', 400);
        }

        return response('OK', 200);
    }
}
