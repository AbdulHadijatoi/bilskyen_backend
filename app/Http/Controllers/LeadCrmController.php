<?php

namespace App\Http\Controllers;

use App\Constants\LeadStage;
use App\Models\Lead;
use App\Models\LeadLostReason;
use App\Models\LeadNote;
use App\Models\LeadReminder;
use App\Models\LeadTask;
use App\Services\DealerContextService;
use App\Services\LeadActivityService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadCrmController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
        private SubscriptionFeatureService $subscriptionFeatureService,
        private LeadActivityService $leadActivityService,
    ) {}

    public function activities(Request $request, int $leadId): JsonResponse
    {
        $lead = $this->resolveLead($request, $leadId);

        $activities = $lead->activities()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate($request->integer('limit', 30));

        return $this->paginated($activities);
    }

    public function notes(Request $request, int $leadId): JsonResponse
    {
        $lead = $this->resolveLead($request, $leadId);

        $notes = $lead->notes()
            ->with('user:id,name')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();

        return $this->success($notes);
    }

    public function storeNote(Request $request, int $leadId): JsonResponse
    {
        $this->assertCrmPermission($request, 'notes');
        $lead = $this->resolveLead($request, $leadId);

        $data = $request->validate([
            'body' => 'required|string|max:5000',
            'is_pinned' => 'sometimes|boolean',
        ]);

        $note = LeadNote::create([
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'is_pinned' => $data['is_pinned'] ?? false,
        ]);

        $this->leadActivityService->log(
            $lead,
            'note',
            __('messages.crm.activity.note_added'),
            $request->user(),
            ['note_id' => $note->id]
        );

        return $this->created($note->load('user:id,name'));
    }

    public function tasks(Request $request, int $leadId): JsonResponse
    {
        $lead = $this->resolveLead($request, $leadId);

        $tasks = $lead->tasks()
            ->with(['assignedUser:id,name', 'createdBy:id,name'])
            ->orderByRaw('completed_at IS NOT NULL')
            ->orderBy('due_at')
            ->get();

        return $this->success($tasks);
    }

    public function storeTask(Request $request, int $leadId): JsonResponse
    {
        $this->assertCrmPermission($request, 'tasks');
        $lead = $this->resolveLead($request, $leadId);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'assigned_user_id' => 'nullable|exists:users,id',
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high'])],
            'due_at' => 'nullable|date',
        ]);

        $task = LeadTask::create([
            'lead_id' => $lead->id,
            'created_by_user_id' => $request->user()->id,
            'assigned_user_id' => $data['assigned_user_id'] ?? $request->user()->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'due_at' => $data['due_at'] ?? null,
        ]);

        $this->leadActivityService->log(
            $lead,
            'task',
            __('messages.crm.activity.task_created', ['title' => $task->title]),
            $request->user(),
            ['task_id' => $task->id]
        );

        return $this->created($task->load(['assignedUser:id,name', 'createdBy:id,name']));
    }

    public function updateTask(Request $request, int $leadId, int $taskId): JsonResponse
    {
        $this->assertCrmPermission($request, 'tasks');
        $lead = $this->resolveLead($request, $leadId);

        $task = LeadTask::where('lead_id', $lead->id)->findOrFail($taskId);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'assigned_user_id' => 'nullable|exists:users,id',
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high'])],
            'due_at' => 'nullable|date',
            'completed' => 'sometimes|boolean',
        ]);

        if (array_key_exists('completed', $data)) {
            $task->completed_at = $data['completed'] ? now() : null;
        }
        if (isset($data['title'])) {
            $task->title = $data['title'];
        }
        if (array_key_exists('description', $data)) {
            $task->description = $data['description'];
        }
        if (array_key_exists('assigned_user_id', $data)) {
            $task->assigned_user_id = $data['assigned_user_id'];
        }
        if (isset($data['priority'])) {
            $task->priority = $data['priority'];
        }
        if (array_key_exists('due_at', $data)) {
            $task->due_at = $data['due_at'];
        }
        $task->save();

        if ($task->wasChanged('completed_at') && $task->completed_at) {
            $this->leadActivityService->log(
                $lead,
                'task_completed',
                __('messages.crm.activity.task_completed', ['title' => $task->title]),
                $request->user(),
                ['task_id' => $task->id]
            );
        }

        return $this->success($task->fresh(['assignedUser:id,name', 'createdBy:id,name']));
    }

    public function lostReasons(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        $reasons = LeadLostReason::query()
            ->where(function ($q) use ($dealer) {
                $q->whereNull('dealer_id')->orWhere('dealer_id', $dealer->id);
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->success($reasons);
    }

    public function storeReminder(Request $request, int $leadId): JsonResponse
    {
        $this->assertCrmPermission($request, 'tasks');
        $lead = $this->resolveLead($request, $leadId);

        $data = $request->validate([
            'remind_at' => 'required|date|after:now',
            'channel' => ['nullable', Rule::in(['in_app', 'email'])],
        ]);

        $reminder = LeadReminder::create([
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'remind_at' => $data['remind_at'],
            'channel' => $data['channel'] ?? 'in_app',
        ]);

        $this->leadActivityService->log(
            $lead,
            'reminder',
            __('messages.crm.activity.reminder_set'),
            $request->user(),
            ['reminder_id' => $reminder->id]
        );

        return $this->created($reminder);
    }

    private function resolveLead(Request $request, int $leadId): Lead
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        if (! $this->subscriptionFeatureService->hasFeature($dealer, 'lead_management')) {
            abort(403, __('messages.api.lead_management_not_in_plan'));
        }

        return Lead::where('dealer_id', $dealer->id)->findOrFail($leadId);
    }

    private function assertCrmPermission(Request $request, string $suffix): void
    {
        $user = $request->user();
        if (! $user->hasAnyPermission(["dealer.crm.{$suffix}", "staff.crm.{$suffix}", 'dealer.leads.update', 'staff.leads.update'])) {
            abort(403, __('messages.errors.no_required_permissions', ['permissions' => "crm.{$suffix}"]));
        }
    }
}
