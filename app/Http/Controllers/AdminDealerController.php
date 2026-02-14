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
     * List dealers for dropdowns (id, name, email).
     * Name = owner name, or slug, or cvr. Email = owner email.
     */
    public function list(Request $request): JsonResponse
    {
        $dealers = Dealer::with('owner')
            ->orderBy('id')
            ->get();

        $items = $dealers->map(function (Dealer $dealer) {
            $name = $dealer->owner?->name ?? $dealer->slug ?? $dealer->cvr ?? 'Dealer #' . $dealer->id;
            $email = $dealer->owner?->email ?? '';
            return [
                'id' => $dealer->id,
                'name' => $name,
                'email' => $email,
            ];
        });

        return $this->success($items->values()->all());
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
