<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * Internal Auth Session Controller
 * Handles session management (sign-out, get-session, revoke-session, update-user)
 * Called by AuthController facade
 */
class AuthSessionController extends Controller
{
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

        return $this->success(['message' => 'Signed out successfully']);
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

        return $this->success(['message' => 'Session revoked successfully']);
    }

    /**
     * Update user profile
     */
    public function updateUser(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:2|max:100',
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string',
            'image' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user->update($validator->validated());
        $user->load('roles');

        // Match frontend expected response format (camelCase)
        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'emailVerified' => $user->email_verified_at !== null,
                'phone' => $user->phone,
                'address' => $user->address,
                'image' => $user->image ?? null,
            ],
        ]);
    }
}

