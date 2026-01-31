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
        $user = $request->user();
        $dealer = $user->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        // Include user data in response
        $response = $dealer->toArray();
        $response['user'] = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];

        return $this->success($response);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $user->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        // Validate dealer fields
        $dealerValidation = $request->validate([
            'cvr' => 'sometimes|string|max:20',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string',
            'postcode' => 'sometimes|string',
            'country_code' => 'sometimes|string|max:2',
        ]);

        // Validate user fields
        $userValidation = $request->validate([
            'name' => 'sometimes|string|min:2|max:100',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:15',
        ]);

        // Update dealer
        if (!empty($dealerValidation)) {
            $dealer->update($dealerValidation);
        }

        // Update user
        if (!empty($userValidation)) {
            $user->update($userValidation);
        }

        // Reload relationships
        $dealer->refresh();
        $user->refresh();

        // Include updated user data in response
        $response = $dealer->toArray();
        $response['user'] = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];

        return $this->success($response);
    }
}

