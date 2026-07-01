<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\Payments\StripePaymentProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()->with('dealer.owner')->orderByDesc('id');

        if ($request->filled('dealer_id')) {
            $query->where('dealer_id', $request->integer('dealer_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $this->paginated($query->paginate($request->integer('limit', 20)));
    }
}
