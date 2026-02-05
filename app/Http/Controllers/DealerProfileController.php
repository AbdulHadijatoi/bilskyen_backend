<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use App\Services\AuditLogService;
use App\Services\DealerContextService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Dealer Profile Controller
 */
class DealerProfileController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService,
        private DealerContextService $dealerContextService
    ) {}
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $user->dealer;
        
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
        $dealer = $this->dealerContextService->requireDealer($user);

        // Store before state for audit log
        $dealerBefore = $dealer->only(['cvr', 'address', 'city', 'postcode', 'country_code']);
        $userBefore = $user->only(['name', 'email', 'phone']);

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

        // Audit log
        try {
            $changes = [];
            $before = [];
            
            // Track dealer changes
            foreach ($dealerValidation as $key => $value) {
                if (isset($dealerBefore[$key]) && $dealerBefore[$key] != $value) {
                    $before['dealer_' . $key] = $dealerBefore[$key];
                    $changes['dealer_' . $key] = $value;
                }
            }
            
            // Track user changes
            foreach ($userValidation as $key => $value) {
                if (isset($userBefore[$key]) && $userBefore[$key] != $value) {
                    $before['user_' . $key] = $userBefore[$key];
                    $changes['user_' . $key] = $value;
                }
            }
            
            if (!empty($changes)) {
                $this->auditLogService->logUpdate(
                    $user,
                    'Dealer',
                    $dealer->id,
                    $before,
                    $changes,
                    $request,
                    'Dealer',
                    $dealer->id,
                    'Dealer profile updated',
                    ['dealer', 'profile', 'update']
                );
            }
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for dealer profile update', [
                'dealer_id' => $dealer->id,
                'error' => $e->getMessage(),
            ]);
        }

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

