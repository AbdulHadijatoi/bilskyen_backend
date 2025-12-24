<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * Internal Auth Login Controller
 * Handles login, logout, refresh, and me endpoints
 * Called by AuthController facade
 */
class AuthLoginController extends Controller
{
    public function __construct(
        private RolePermissionService $rolePermissionService
    ) {}

    /**
     * JWT Login - Authenticate user and receive tokens
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|max:128',
        ]);

        // Normalize email to lowercase
        $credentials['email'] = strtolower($credentials['email']);

        // Attempt authentication
        if (!$token = auth('api')->attempt($credentials)) {
            return $this->error('Invalid credentials', [
                'email' => ['These credentials do not match our records.'],
            ], 401);
        }

        $user = auth('api')->user();
        $user->load('roles');

        // Check if user is banned
        if ($user->banned ?? false) {
            auth('api')->logout();
            return $this->error('Account is banned', [
                'ban_reason' => $user->ban_reason ?? null,
                'ban_expires' => $user->ban_expires ?? null,
            ], 403);
        }

        // Generate refresh token with custom claim
        $refreshToken = JWTAuth::customClaims(['type' => 'refresh'])->fromUser($user);

        // Set refresh token as HttpOnly cookie
        $cookie = cookie(
            'refresh_token',
            $refreshToken,
            20160, // 14 days in minutes
            null,
            null,
            true, // secure
            true, // httpOnly
            false, // raw
            'Strict' // sameSite
        );

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'emailVerified' => $user->email_verified_at !== null,
            ],
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl', 30) * 60, // in seconds
        ])->cookie($cookie);
    }

    /**
     * JWT Refresh - Refresh access token using refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $refreshToken = $request->cookie('refresh_token');
            
            if (!$refreshToken) {
                return $this->error('Refresh token not found', null, 401);
            }

            // Authenticate using refresh token
            $user = JWTAuth::setToken($refreshToken)->authenticate();
            
            if (!$user) {
                return $this->error('User not found', null, 404);
            }

            // Check if user is banned
            if ($user->banned ?? false) {
                return $this->error('Account is banned', null, 403);
            }

            // Generate new access token
            $newAccessToken = auth('api')->login($user);

            // Optional: Rotate refresh token (recommended for security)
            $newRefreshToken = JWTAuth::customClaims(['type' => 'refresh'])->fromUser($user);
            
            $cookie = cookie(
                'refresh_token',
                $newRefreshToken,
                20160, // 14 days
                null,
                null,
                true, // secure
                true, // httpOnly
                false, // raw
                'Strict' // sameSite
            );

            return $this->success([
                'access_token' => $newAccessToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl', 30) * 60, // in seconds
            ])->cookie($cookie);
        } catch (JWTException $e) {
            return $this->error('Refresh token expired or invalid', null, 401);
        } catch (\Exception $e) {
            return $this->error('Failed to refresh token', null, 500);
        }
    }

    /**
     * JWT Logout - Logout user and invalidate tokens
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Invalidate current access token
            JWTAuth::parseToken()->invalidate();

            // Clear refresh token cookie
            $cookie = cookie()->forget('refresh_token');

            return $this->success(['message' => 'Logged out successfully'])->cookie($cookie);
        } catch (JWTException $e) {
            // Token might already be invalid, but we still want to clear the cookie
            $cookie = cookie()->forget('refresh_token');
            
            return $this->success(['message' => 'Logged out successfully'])->cookie($cookie);
        } catch (\Exception $e) {
            return $this->error('Failed to logout', null, 500);
        }
    }

    /**
     * JWT Me - Get current authenticated user information
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->unauthorized('Unauthenticated');
            }

            $user->load('roles');

            return $this->success([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'emailVerified' => $user->email_verified_at !== null,
                'phone' => $user->phone,
                'address' => $user->address,
                'image' => $user->image ?? null,
                'banned' => $user->banned ?? false,
                'created_at' => $user->created_at,
            ]);
        } catch (\Exception $e) {
            return $this->unauthorized('Unauthenticated');
        }
    }
}

