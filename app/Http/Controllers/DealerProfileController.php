<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Dealer Profile Controller
 */
class DealerProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        return $this->success($dealer);
    }

    public function update(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $request->validate([
            'cvr' => 'sometimes|string|max:20',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string',
            'postcode' => 'sometimes|string',
            'country_code' => 'sometimes|string|max:2',
        ]);

        $dealer->update($request->only(['cvr', 'address', 'city', 'postcode', 'country_code']));

        return $this->success($dealer);
    }
}

