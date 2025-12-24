<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Lead Controller for Dealer
 */
class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // TODO: Implement lead listing with filters
        $leads = Lead::query()
            ->where('dealer_id', $request->user()->dealers()->first()->id ?? null)
            ->paginate($request->get('limit', 15));

        return $this->paginated($leads);
    }

    public function show(int $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        return $this->success($lead);
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        // TODO: Implement lead assignment
        return $this->success(['message' => 'Lead assigned successfully']);
    }

    public function updateStage(Request $request, int $id): JsonResponse
    {
        // TODO: Implement stage update with history
        return $this->success(['message' => 'Lead stage updated successfully']);
    }

    public function getMessages(int $id): JsonResponse
    {
        // TODO: Implement chat messages retrieval
        return $this->success([]);
    }

    public function sendMessage(Request $request, int $id): JsonResponse
    {
        // TODO: Implement message sending
        return $this->success(['message' => 'Message sent successfully']);
    }
}

