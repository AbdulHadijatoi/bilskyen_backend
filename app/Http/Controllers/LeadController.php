<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\ChatThread;
use App\Models\ChatMessage;
use App\Models\LeadStageHistory;
use App\Constants\LeadStage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * Lead Controller for Dealer
 */
class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $query = Lead::with(['vehicle', 'buyerUser', 'assignedUser', 'leadStage', 'source'])
            ->where('dealer_id', $dealer->id);

        // Apply filters
        if ($request->has('stage_id')) {
            $query->where('lead_stage_id', $request->input('stage_id'));
        }

        if ($request->has('assigned_user_id')) {
            $query->where('assigned_user_id', $request->input('assigned_user_id'));
        }

        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->input('vehicle_id'));
        }

        // Apply sorting
        $sortBy = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $leads = $query->paginate($request->get('limit', 15));

        return $this->paginated($leads);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $lead = Lead::with([
            'vehicle',
            'buyerUser',
            'assignedUser',
            'leadStage',
            'source',
            'stageHistory.changedByUser'
        ])
        ->where('dealer_id', $dealer->id)
        ->findOrFail($id);

        return $this->success($lead);
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $lead = Lead::where('dealer_id', $dealer->id)->findOrFail($id);
        $lead->assigned_user_id = $request->user_id;
        $lead->save();

        return $this->success($lead->load('assignedUser'));
    }

    public function updateStage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'stage_id' => ['required', Rule::in(LeadStage::values())],
        ]);

        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $lead = Lead::where('dealer_id', $dealer->id)->findOrFail($id);
        
        // Create stage history entry
        LeadStageHistory::create([
            'lead_id' => $lead->id,
            'from_stage_id' => $lead->lead_stage_id,
            'to_stage_id' => $request->stage_id,
            'changed_by_user_id' => $request->user()->id,
            'changed_at' => now(),
        ]);

        // Update lead stage
        $lead->lead_stage_id = $request->stage_id;
        $lead->last_activity_at = now();
        $lead->save();

        return $this->success($lead->load('leadStage', 'stageHistory'));
    }

    public function getMessages(int $id, Request $request): JsonResponse
    {
        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $lead = Lead::where('dealer_id', $dealer->id)->findOrFail($id);
        
        // Get or create chat thread for this lead
        $thread = ChatThread::firstOrCreate(
            ['lead_id' => $lead->id],
            ['created_at' => now()]
        );

        // Get messages for this thread
        $messages = ChatMessage::with('sender')
            ->where('thread_id', $thread->id)
            ->orderBy('created_at', 'asc')
            ->paginate($request->get('limit', 50));

        return $this->paginated($messages);
    }

    public function sendMessage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'is_internal' => 'sometimes|boolean',
        ]);

        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $lead = Lead::where('dealer_id', $dealer->id)->findOrFail($id);
        
        // Get or create chat thread for this lead
        $thread = ChatThread::firstOrCreate(
            ['lead_id' => $lead->id],
            ['created_at' => now()]
        );

        // Create message
        $chatMessage = ChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $request->user()->id,
            'message' => $request->message,
            'is_internal' => $request->boolean('is_internal', false),
            'created_at' => now(),
        ]);

        // Update lead last activity
        $lead->last_activity_at = now();
        $lead->save();

        return $this->created($chatMessage->load('sender'));
    }
}

