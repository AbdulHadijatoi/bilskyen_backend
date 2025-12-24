<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AuditActorType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/**
 * Centralized Audit Logging Service
 * Logs admin and system actions with payload diffs
 * Uses Redis for high-performance logging (mandatory dependency)
 */
class AuditLogService
{
    /**
     * Log an action
     */
    public function log(
        int $actorId,
        int $actorTypeId,
        string $action,
        string $targetType,
        int $targetId,
        ?array $payloadBefore = null,
        ?array $payloadAfter = null,
        ?Request $request = null
    ): AuditLog {
        try {
            // Calculate payload diff
            $payloadDiff = $this->calculateDiff($payloadBefore, $payloadAfter);

            // Get request information
            $ipAddress = $request?->ip();
            $userAgent = $request?->userAgent();

            // Prepare metadata with full audit information
            $metadata = [
                'payload_before' => $payloadBefore,
                'payload_after' => $payloadAfter,
                'payload_diff' => $payloadDiff,
                'user_agent' => $userAgent,
            ];

            // Create audit log entry
            $auditLog = AuditLog::create([
                'actor_id' => $actorId,
                'audit_actor_type_id' => $actorTypeId,
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'metadata' => $metadata,
                'ip_address' => $ipAddress,
                'created_at' => now(),
            ]);

            // Also store in Redis for fast retrieval (optional, for high-volume scenarios)
            try {
                $redisKey = "audit_log:{$auditLog->id}";
                Cache::put($redisKey, $auditLog->toArray(), now()->addDays(7));
            } catch (\Exception $e) {
                // Log but don't fail - Redis is optional for audit logs retrieval
                Log::warning('Failed to cache audit log in Redis', [
                    'audit_log_id' => $auditLog->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $auditLog;
        } catch (\Exception $e) {
            // Fail fast if database is unavailable (critical for compliance)
            Log::error('Failed to create audit log', [
                'actor_id' => $actorId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Audit logging service unavailable', 503);
        }
    }

    /**
     * Log admin user ban
     */
    public function logUserBan(int $adminId, int $userId, array $userData, Request $request): AuditLog
    {
        return $this->log(
            $adminId,
            \App\Models\AuditActorType::ADMIN,
            'ban',
            'User',
            $userId,
            $userData,
            array_merge($userData, ['banned' => true]),
            $request
        );
    }

    /**
     * Log admin user unban
     */
    public function logUserUnban(int $adminId, int $userId, array $userData, Request $request): AuditLog
    {
        return $this->log(
            $adminId,
            \App\Models\AuditActorType::ADMIN,
            'unban',
            'User',
            $userId,
            $userData,
            array_merge($userData, ['banned' => false]),
            $request
        );
    }

    /**
     * Log vehicle status change
     */
    public function logVehicleStatusChange(
        int $actorId,
        int $actorTypeId,
        int $vehicleId,
        array $before,
        array $after,
        Request $request
    ): AuditLog {
        return $this->log(
            $actorId,
            $actorTypeId,
            'status_change',
            'Vehicle',
            $vehicleId,
            $before,
            $after,
            $request
        );
    }

    /**
     * Log soft delete
     */
    public function logDelete(
        int $actorId,
        int $actorTypeId,
        string $targetType,
        int $targetId,
        array $targetData,
        Request $request
    ): AuditLog {
        return $this->log(
            $actorId,
            $actorTypeId,
            'delete',
            $targetType,
            $targetId,
            $targetData,
            array_merge($targetData, ['deleted_at' => now()->toDateTimeString()]),
            $request
        );
    }

    /**
     * Log plan creation
     */
    public function logPlanCreate(int $adminId, int $planId, array $planData, Request $request): AuditLog
    {
        return $this->log(
            $adminId,
            \App\Models\AuditActorType::ADMIN,
            'create',
            'Plan',
            $planId,
            null,
            $planData,
            $request
        );
    }

    /**
     * Log plan update
     */
    public function logPlanUpdate(
        int $adminId,
        int $planId,
        array $before,
        array $after,
        Request $request
    ): AuditLog {
        return $this->log(
            $adminId,
            \App\Models\AuditActorType::ADMIN,
            'update',
            'Plan',
            $planId,
            $before,
            $after,
            $request
        );
    }

    /**
     * Log subscription status change
     */
    public function logSubscriptionStatusChange(
        int $adminId,
        int $subscriptionId,
        array $before,
        array $after,
        Request $request
    ): AuditLog {
        return $this->log(
            $adminId,
            \App\Models\AuditActorType::ADMIN,
            'status_change',
            'DealerSubscription',
            $subscriptionId,
            $before,
            $after,
            $request
        );
    }

    /**
     * Calculate diff between two arrays
     */
    protected function calculateDiff(?array $before, ?array $after): array
    {
        if ($before === null && $after === null) {
            return [];
        }

        if ($before === null) {
            return ['added' => $after];
        }

        if ($after === null) {
            return ['removed' => $before];
        }

        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));

        foreach ($allKeys as $key) {
            $beforeValue = $before[$key] ?? null;
            $afterValue = $after[$key] ?? null;

            if ($beforeValue !== $afterValue) {
                $diff[$key] = [
                    'before' => $beforeValue,
                    'after' => $afterValue,
                ];
            }
        }

        return $diff;
    }
}

