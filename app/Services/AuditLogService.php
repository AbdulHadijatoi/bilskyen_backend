<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AuditActorType;
use App\Models\User;
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
     * Determine actor type from user roles
     */
    public function determineActorType(User $user): int
    {
        if ($user->hasRole('admin')) {
            return AuditActorType::ADMIN;
        }
        
        if ($user->hasRole('dealer')) {
            return AuditActorType::DEALER;
        }
        
        if ($user->hasRole('staff')) {
            return AuditActorType::STAFF;
        }
        
        // Default to seller
        return AuditActorType::SELLER;
    }

    /**
     * Get dealer ID from user
     */
    public function getDealerIdFromUser(User $user): ?int
    {
        // Get dealer from dealer relationship
        $dealer = $user->dealer;
        if ($dealer) {
            return $dealer->id;
        }
        
        return null;
    }

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
        ?Request $request = null,
        ?string $relatedTargetType = null,
        ?int $relatedTargetId = null,
        ?string $description = null,
        ?array $tags = null,
        ?string $severity = 'info',
        ?int $dealerId = null,
        ?string $status = 'success',
        ?string $errorMessage = null
    ): AuditLog {
        try {
            // Calculate payload diff
            $payloadDiff = $this->calculateDiff($payloadBefore, $payloadAfter);

            // Get request information
            $ipAddress = $request?->ip();
            $userAgent = $request?->userAgent();
            $requestMethod = $request?->method();
            $requestUrl = $request?->fullUrl();

            // Prepare metadata with full audit information
            $metadata = [
                'payload_before' => $payloadBefore,
                'payload_after' => $payloadAfter,
                'payload_diff' => $payloadDiff,
            ];

            // Create audit log entry with all new fields
            $auditLog = AuditLog::create([
                'actor_id' => $actorId,
                'audit_actor_type_id' => $actorTypeId,
                'dealer_id' => $dealerId,
                'action' => $action,
                'status' => $status,
                'error_message' => $errorMessage,
                'request_method' => $requestMethod,
                'request_url' => $requestUrl,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'related_target_type' => $relatedTargetType,
                'related_target_id' => $relatedTargetId,
                'description' => $description,
                'tags' => $tags,
                'severity' => $severity,
                'metadata' => $metadata,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
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
     * Convenience method to log create operations
     */
    public function logCreate(
        User $user,
        string $targetType,
        int $targetId,
        array $payloadAfter,
        Request $request,
        ?string $relatedTargetType = null,
        ?int $relatedTargetId = null,
        ?string $description = null,
        ?array $tags = null,
        ?string $severity = 'info'
    ): ?AuditLog {
        try {
            $actorTypeId = $this->determineActorType($user);
            $dealerId = $this->getDealerIdFromUser($user);
            
            return $this->log(
                $user->id,
                $actorTypeId,
                'create',
                $targetType,
                $targetId,
                null,
                $payloadAfter,
                $request,
                $relatedTargetType,
                $relatedTargetId,
                $description,
                $tags,
                $severity,
                $dealerId
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for create operation', [
                'user_id' => $user->id,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Convenience method to log create operations for guest users
     * Uses SYSTEM actor type when user is null
     */
    public function logCreateForGuest(
        ?User $user,
        string $targetType,
        int $targetId,
        array $payloadAfter,
        Request $request,
        ?string $relatedTargetType = null,
        ?int $relatedTargetId = null,
        ?string $description = null,
        ?array $tags = null,
        ?string $severity = 'info'
    ): ?AuditLog {
        try {
            // If user is authenticated, use regular logCreate
            if ($user) {
                return $this->logCreate(
                    $user,
                    $targetType,
                    $targetId,
                    $payloadAfter,
                    $request,
                    $relatedTargetType,
                    $relatedTargetId,
                    $description,
                    $tags,
                    $severity
                );
            }
            
            // For guest users, use SYSTEM actor type
            return $this->log(
                0, // actor_id = 0 for system/guest actions
                AuditActorType::SYSTEM,
                'create',
                $targetType,
                $targetId,
                null,
                $payloadAfter,
                $request,
                $relatedTargetType,
                $relatedTargetId,
                $description,
                $tags,
                $severity,
                null // dealer_id is null for guest users
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for guest create operation', [
                'target_type' => $targetType,
                'target_id' => $targetId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Convenience method to log update operations
     */
    public function logUpdate(
        User $user,
        string $targetType,
        int $targetId,
        array $payloadBefore,
        array $payloadAfter,
        Request $request,
        ?string $relatedTargetType = null,
        ?int $relatedTargetId = null,
        ?string $description = null,
        ?array $tags = null,
        ?string $severity = 'info'
    ): ?AuditLog {
        try {
            $actorTypeId = $this->determineActorType($user);
            $dealerId = $this->getDealerIdFromUser($user);
            
            return $this->log(
                $user->id,
                $actorTypeId,
                'update',
                $targetType,
                $targetId,
                $payloadBefore,
                $payloadAfter,
                $request,
                $relatedTargetType,
                $relatedTargetId,
                $description,
                $tags,
                $severity,
                $dealerId
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for update operation', [
                'user_id' => $user->id,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Convenience method to log delete operations
     */
    public function logDelete(
        User $user,
        string $targetType,
        int $targetId,
        array $payloadBefore,
        Request $request,
        ?string $relatedTargetType = null,
        ?int $relatedTargetId = null,
        ?string $description = null,
        ?array $tags = null,
        ?string $severity = 'info'
    ): ?AuditLog {
        try {
            $actorTypeId = $this->determineActorType($user);
            $dealerId = $this->getDealerIdFromUser($user);
            
            return $this->log(
                $user->id,
                $actorTypeId,
                'delete',
                $targetType,
                $targetId,
                $payloadBefore,
                null,
                $request,
                $relatedTargetType,
                $relatedTargetId,
                $description,
                $tags,
                $severity,
                $dealerId
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for delete operation', [
                'user_id' => $user->id,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'error' => $e->getMessage(),
            ]);
            return null;
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
            $request,
            null,
            null,
            'User banned by admin',
            ['user', 'ban', 'admin'],
            'warning'
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
            $request,
            null,
            null,
            'User unbanned by admin',
            ['user', 'unban', 'admin'],
            'info'
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
            $request,
            null,
            null,
            'Vehicle status changed',
            ['vehicle', 'status']
        );
    }

    /**
     * Log soft delete (legacy method - use logDelete with User instead)
     */
    public function logDeleteLegacy(
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
            $request,
            null,
            null,
            "{$targetType} deleted",
            [strtolower($targetType), 'delete']
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
            $request,
            null,
            null,
            'Plan created',
            ['plan', 'subscription']
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
            $request,
            null,
            null,
            'Plan updated',
            ['plan', 'subscription']
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
            $request,
            null,
            null,
            'Subscription status changed',
            ['subscription', 'status']
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

