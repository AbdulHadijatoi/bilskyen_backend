<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\DealerContextService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * Dealer Audit Log Controller
 * Provides dealer-scoped audit log viewing (admin only)
 */
class DealerAuditLogController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
        private SubscriptionFeatureService $subscriptionFeatureService
    ) {}

    /**
     * Get audit logs for the current dealer
     * Supports filtering by actor_id, target_type, action, date range, severity, status
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        // Check if audit_logs feature is enabled
        // Note: If 'audit_logs' feature doesn't exist in subscription, default to false (no access)
        if (!$this->subscriptionFeatureService->hasFeature($dealer, 'audit_logs')) {
            return $this->error(
                'Audit logs access is not available in your current subscription plan. Please upgrade to access this feature.',
                403
            );
        }

        $query = AuditLog::with(['auditActorType'])
            ->where('dealer_id', $dealer->id);

        // Apply filters
        if ($request->has('actor_id')) {
            $query->where('actor_id', $request->actor_id);
        }

        if ($request->has('target_type')) {
            $query->where('target_type', $request->target_type);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Date range filtering
        if ($request->has('date_from')) {
            try {
                $dateFrom = Carbon::parse($request->date_from)->startOfDay();
                $query->where('created_at', '>=', $dateFrom);
            } catch (\Exception $e) {
                return $this->validationError(['date_from' => ['Invalid date format']]);
            }
        }

        if ($request->has('date_to')) {
            try {
                $dateTo = Carbon::parse($request->date_to)->endOfDay();
                $query->where('created_at', '<=', $dateTo);
            } catch (\Exception $e) {
                return $this->validationError(['date_to' => ['Invalid date format']]);
            }
        }

        // Search in description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('target_type', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');
        
        // Validate sort column
        $allowedSorts = ['created_at', 'action', 'target_type', 'severity', 'status'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        
        $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');

        $logs = $query->paginate($request->get('limit', 15));

        // Transform logs to include actor type name
        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'actor_id' => $log->actor_id,
                'actor_type' => $log->auditActorType->name ?? 'Unknown',
                'dealer_id' => $log->dealer_id,
                'action' => $log->action,
                'target_type' => $log->target_type,
                'target_id' => $log->target_id,
                'related_target_type' => $log->related_target_type,
                'related_target_id' => $log->related_target_id,
                'description' => $log->description,
                'status' => $log->status,
                'severity' => $log->severity,
                'tags' => $log->tags,
                'metadata' => $log->metadata,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'request_method' => $log->request_method,
                'request_url' => $log->request_url,
                'created_at' => $log->created_at?->toISOString(),
            ];
        });

        return $this->paginated($logs);
    }
}
