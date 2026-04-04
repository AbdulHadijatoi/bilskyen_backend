<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\ChatThread;
use App\Models\ChatMessage;
use App\Models\LeadStageHistory;
use App\Constants\LeadStage;
use App\Services\AuditLogService;
use App\Services\DealerContextService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

/**
 * Lead Controller for Dealer
 */
class LeadController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService,
        private DealerContextService $dealerContextService,
        private SubscriptionFeatureService $subscriptionFeatureService
    ) {}
    public function index(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealer;
        
        if (!$dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        // Check if lead_management feature is enabled
        if (!$this->subscriptionFeatureService->hasFeature($dealer, 'lead_management')) {
            return $this->error(
                __('messages.api.lead_management_not_in_plan'),
                [],
                403
            );
        }

        $query = Lead::with(['vehicle', 'buyerUser', 'assignedUser', 'leadStage', 'source', 'enquiry'])
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
        $dealer = $request->user()->dealer;
        
        if (!$dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $lead = Lead::with([
            'vehicle',
            'buyerUser',
            'assignedUser',
            'leadStage',
            'source',
            'stageHistory.changedByUser',
            'enquiry'
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

        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        $lead = Lead::where('dealer_id', $dealer->id)->findOrFail($id);
        $oldAssignedUserId = $lead->assigned_user_id;
        $lead->assigned_user_id = $request->user_id;
        $lead->save();
        $lead->refresh();

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $user,
                'Lead',
                $lead->id,
                ['assigned_user_id' => $oldAssignedUserId],
                ['assigned_user_id' => $request->user_id],
                $request,
                'Dealer',
                $dealer->id,
                "Lead assigned to user ID: {$request->user_id}",
                ['lead', 'dealer', 'assign', 'update']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for lead assignment', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($lead->load('assignedUser'));
    }

    public function updateStage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'stage_id' => ['required', Rule::in(LeadStage::values())],
        ]);

        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        $lead = Lead::where('dealer_id', $dealer->id)->findOrFail($id);
        $oldStageId = $lead->lead_stage_id;
        
        // Create stage history entry
        LeadStageHistory::create([
            'lead_id' => $lead->id,
            'from_stage_id' => $oldStageId,
            'to_stage_id' => $request->stage_id,
            'changed_by_user_id' => $user->id,
            'changed_at' => now(),
        ]);

        // Update lead stage
        $lead->lead_stage_id = $request->stage_id;
        $lead->last_activity_at = now();
        $lead->save();
        $lead->refresh();

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $user,
                'Lead',
                $lead->id,
                ['lead_stage_id' => $oldStageId],
                ['lead_stage_id' => $request->stage_id],
                $request,
                'Dealer',
                $dealer->id,
                "Lead stage updated: {$oldStageId} -> {$request->stage_id}",
                ['lead', 'dealer', 'stage', 'update']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for lead stage update', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($lead->load('leadStage', 'stageHistory'));
    }

    public function updateIntent(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'intent_id' => 'required|exists:lead_intents,id',
        ]);

        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        $lead = Lead::where('dealer_id', $dealer->id)->findOrFail($id);
        $oldIntentId = $lead->lead_intent_id;
        $lead->lead_intent_id = $request->intent_id;
        $lead->last_activity_at = now();
        $lead->save();
        $lead->refresh();

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $user,
                'Lead',
                $lead->id,
                ['lead_intent_id' => $oldIntentId],
                ['lead_intent_id' => $request->intent_id],
                $request,
                'Dealer',
                $dealer->id,
                "Lead intent updated: {$oldIntentId} -> {$request->intent_id}",
                ['lead', 'dealer', 'intent', 'update']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for lead intent update', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($lead->load('leadIntent'));
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'category_id' => 'required|exists:lead_categories,id',
        ]);

        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        $lead = Lead::where('dealer_id', $dealer->id)->findOrFail($id);
        $oldCategoryId = $lead->lead_category_id;
        $lead->lead_category_id = $request->category_id;
        $lead->last_activity_at = now();
        $lead->save();
        $lead->refresh();

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $user,
                'Lead',
                $lead->id,
                ['lead_category_id' => $oldCategoryId],
                ['lead_category_id' => $request->category_id],
                $request,
                'Dealer',
                $dealer->id,
                "Lead category updated: {$oldCategoryId} -> {$request->category_id}",
                ['lead', 'dealer', 'category', 'update']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for lead category update', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($lead->load('leadCategory'));
    }

    public function getMessages(int $id, Request $request): JsonResponse
    {
        $dealer = $request->user()->dealer;
        
        if (!$dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
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

        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        $lead = Lead::where('dealer_id', $dealer->id)->findOrFail($id);
        
        // Get or create chat thread for this lead
        $thread = ChatThread::firstOrCreate(
            ['lead_id' => $lead->id],
            ['created_at' => now()]
        );

        // Create message
        $chatMessage = ChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'message' => $request->message,
            'is_internal' => $request->boolean('is_internal', false),
            'created_at' => now(),
        ]);

        // Update lead last activity
        $lead->last_activity_at = now();
        $lead->save();

        // Audit log
        try {
            $messageType = $request->boolean('is_internal', false) ? 'internal note' : 'message';
            $this->auditLogService->logCreate(
                $user,
                'ChatMessage',
                $chatMessage->id,
                [
                    'thread_id' => $thread->id,
                    'message_preview' => substr($request->message, 0, 100),
                    'is_internal' => $request->boolean('is_internal', false),
                ],
                $request,
                'Lead',
                $lead->id,
                "Sent {$messageType} to lead",
                ['lead', 'dealer', 'communication', 'message']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for lead message', [
                'lead_id' => $lead->id,
                'message_id' => $chatMessage->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->created($chatMessage->load('sender'));
    }
}

