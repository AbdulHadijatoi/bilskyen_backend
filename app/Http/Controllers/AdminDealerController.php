<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin Dealer Controller
 */
class AdminDealerController extends Controller
{
    /**
     * List all dealers
     */
    public function index(Request $request): JsonResponse
    {
        $query = Dealer::with('staff.user.roles');

        // Apply search filter
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('cvr', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $dealers = $query->paginate($request->get('limit', 15));

        return $this->paginated($dealers);
    }

    /**
     * Get dealer details
     */
    public function show(int $id): JsonResponse
    {
        $dealer = Dealer::with(['staff.user.roles', 'subscriptions.plan', 'vehicles'])
            ->findOrFail($id);

        return $this->success($dealer);
    }
}
