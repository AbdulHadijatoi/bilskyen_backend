<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * Admin Audit Log Controller
 * Provides admin-scoped audit log viewing with full filtering and detail access
 */
class AdminAuditLogController extends Controller
{
    /**
     * Get audit logs with filtering
     * Supports filtering by actor_id, target_type, action, date range, severity, status
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with(['auditActorType']);

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
                return $this->validationError(['date_from' => [__('messages.api.invalid_date_format')]]);
            }
        }

        if ($request->has('date_to')) {
            try {
                $dateTo = Carbon::parse($request->date_to)->endOfDay();
                $query->where('created_at', '<=', $dateTo);
            } catch (\Exception $e) {
                return $this->validationError(['date_to' => [__('messages.api.invalid_date_format')]]);
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
                'error_message' => $log->error_message,
                'duration_ms' => $log->duration_ms,
                'created_at' => $log->created_at?->toISOString(),
            ];
        });

        return $this->paginated($logs);
    }

    /**
     * Get audit log details by ID
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $log = AuditLog::with(['auditActorType'])->find($id);

        if (!$log) {
            return $this->error('Audit log not found', 404);
        }

        // Transform log to include all details
        $logData = [
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
            'error_message' => $log->error_message,
            'duration_ms' => $log->duration_ms,
            'session_id' => $log->session_id,
            'request_id' => $log->request_id,
            'created_at' => $log->created_at?->toISOString(),
        ];

        return $this->success($logData);
    }
}

