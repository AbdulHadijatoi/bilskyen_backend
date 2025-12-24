<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DealerUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Dealer Staff Controller
 */
class DealerStaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $staff = $dealer->users()->paginate($request->get('limit', 15));

        return $this->paginated($staff);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        DealerUser::create([
            'dealer_id' => $dealer->id,
            'user_id' => $request->user_id,
            'role_id' => $request->role_id,
        ]);

        return $this->created(['message' => 'Staff member added successfully']);
    }

    public function update(int $userId, Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $dealerUser = DealerUser::where('dealer_id', $dealer->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $dealerUser->update(['role_id' => $request->role_id]);

        return $this->success($dealerUser);
    }

    public function destroy(int $userId, Request $request): JsonResponse
    {
        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        DealerUser::where('dealer_id', $dealer->id)
            ->where('user_id', $userId)
            ->delete();

        return $this->noContent();
    }
}

