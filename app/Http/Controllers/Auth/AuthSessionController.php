<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * Internal Auth Session Controller
 * Handles session management (sign-out, get-session, revoke-session, update-user)
 * Called by AuthController facade
 */
class AuthSessionController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {}
    /**
     * Sign out user (JWT)
     * Note: JWT tokens are stateless. Client should discard the token.
     * Optionally invalidate token if blacklist is enabled.
     */
    public function signOut(Request $request): JsonResponse
    {
        try {
            // Invalidate current JWT token if blacklist is enabled
            JWTAuth::parseToken()->invalidate();
        } catch (JWTException $e) {
            // Token might already be invalid, continue anyway
        }

        return $this->success(['message' => __('messages.messages.signed_out_successfully')]);
    }

    /**
     * Get current session/user
     */
    public function getSession(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('roles');

        // Match frontend expected response format (camelCase)
        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'emailVerified' => $user->email_verified_at !== null,
                'phone' => $user->phone,
                'address' => $user->address,
                'image' => $user->image ?? null,
                'banned' => $user->banned ?? false,
            ],
        ]);
    }

    /**
     * Revoke current session (JWT)
     * Note: JWT tokens are stateless. Client should discard the token.
     */
    public function revokeSession(Request $request): JsonResponse
    {
        try {
            // Invalidate current JWT token if blacklist is enabled
            JWTAuth::parseToken()->invalidate();
        } catch (JWTException $e) {
            // Token might already be invalid, continue anyway
        }

        return $this->success(['message' => __('messages.messages.session_revoked_successfully')]);
    }

    /**
     * Update user profile
     */
    public function updateUser(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:2|max:100',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:10',
            'image' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $validated = $validator->validated();
        if (isset($validated['email'])) {
            $validated['email'] = strtolower($validated['email']);
        }

        // Capture before state for audit log
        $beforeData = $user->only(['name', 'email', 'phone', 'address', 'postcode', 'image']);

        $user->update($validated);
        $user->load('roles');

        // Log audit trail
        try {
            $afterData = $user->only(['name', 'email', 'phone', 'address', 'postcode', 'image']);
            $this->auditLogService->logUpdate(
                $user,
                'User',
                $user->id,
                $beforeData,
                $afterData,
                $request,
                null,
                null,
                'User profile updated',
                ['user', 'profile']
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for user profile update', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Match frontend expected response format (camelCase)
        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'emailVerified' => $user->email_verified_at !== null,
                'phone' => $user->phone,
                'address' => $user->address,
                'postcode' => $user->postcode,
                'image' => $user->image ?? null,
            ],
        ]);
    }

    /**
     * Delete user account
     * Requires password confirmation for security
     * Soft deletes the user account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|max:128',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Verify password for security
        if (!Hash::check($request->password, $user->password)) {
            return $this->error(__('messages.errors.current_password_incorrect'), [
                'password' => ['The password is incorrect.'],
            ], 401);
        }

        // Store user data for audit log before deletion
        $userData = $user->toArray();
        $userId = $user->id;

        // Soft delete the user
        $user->delete();

        // Invalidate JWT token
        try {
            JWTAuth::parseToken()->invalidate();
        } catch (JWTException $e) {
            // Token might already be invalid, continue anyway
        }

        // Log audit trail
        try {
            $this->auditLogService->logDelete(
                $user, // Use the deleted user instance (still available in memory)
                'User',
                $userId,
                $userData,
                $request,
                null,
                null,
                'User account deleted by user',
                ['user', 'account', 'deletion'],
                'warning'
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for account deletion', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success(['message' => __('messages.messages.account_deleted_successfully')]);
    }
}

