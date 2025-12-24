<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin Audit Log Controller
 */
class AdminAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('auditActorType');

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

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 15));

        return $this->paginated($logs);
    }
}

