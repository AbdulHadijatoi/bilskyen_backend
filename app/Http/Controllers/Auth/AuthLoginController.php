<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DealerStaff;
use App\Models\User;
use App\Services\RolePermissionService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
        private RolePermissionService $rolePermissionService,
        private SubscriptionFeatureService $subscriptionFeatureService
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

            return $this->success(['message' => __('messages.errors.logged_out_success')])->cookie($cookie);
        } catch (JWTException $e) {
            // Token might already be invalid, but we still want to clear the cookie
            $cookie = cookie()->forget('refresh_token');
            
            return $this->success(['message' => __('messages.errors.logged_out_success')])->cookie($cookie);
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

            // Get subscription features for dealer/staff users (not admin)
            $subscriptionFeatures = [];
            if ($user->hasAnyRole(['dealer', 'staff']) && !$user->hasRole('admin')) {
                $dealer = $user->dealer;
                
                // For staff users, get dealer from DealerStaff relationship
                if (!$dealer && $user->hasRole('staff')) {
                    $dealerStaff = DealerStaff::where('user_id', $user->id)->first();
                    if ($dealerStaff) {
                        $dealer = $dealerStaff->dealer;
                    }
                }
                
                if ($dealer) {
                    $subscriptionFeatures = $this->subscriptionFeatureService->getFeatures($dealer);
                }
            }

            // Ensure subscription_features is always an object (not array) for JSON encoding
            // Convert empty array to empty object to ensure JSON encodes as {} not []
            // Non-empty associative arrays will JSON encode as objects automatically
            if (is_array($subscriptionFeatures) && empty($subscriptionFeatures)) {
                $subscriptionFeatures = new \stdClass();
            }

        return $this->success([
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
            'created_at' => $user->created_at,
            'subscription_features' => $subscriptionFeatures,
        ]);
        } catch (\Exception $e) {
            return $this->unauthorized('Unauthenticated');
        }
    }

    /**
     * Panel Login - Authenticate user for Vue.js admin panel
     * Only allows dealer, staff, or admin roles
     * Sellers are rejected and should use Laravel web login instead
     */
    public function panelLogin(Request $request): JsonResponse
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

        // Check if user has allowed role for Vue.js panel login (dealer, staff, or admin only)
        // Sellers are not allowed to login via Vue.js API, but can use Laravel web login
        if (!$user->hasAnyRole(['dealer', 'staff', 'admin'])) {
            auth('api')->logout();
            return $this->error('Invalid account, you can not login here.', [
                'email' => ['Invalid account, you can not login here.'],
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

        // Get subscription features for dealer/staff users (not admin)
        $subscriptionFeatures = [];
        if ($user->hasAnyRole(['dealer', 'staff']) && !$user->hasRole('admin')) {
            $dealer = $user->dealer;
            
            // For staff users, get dealer from DealerStaff relationship
            if (!$dealer && $user->hasRole('staff')) {
                $dealerStaff = DealerStaff::where('user_id', $user->id)->first();
                if ($dealerStaff) {
                    $dealer = $dealerStaff->dealer;
                }
            }
            
            if ($dealer) {
                $subscriptionFeatures = $this->subscriptionFeatureService->getFeatures($dealer);
            }
        }

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'emailVerified' => $user->email_verified_at !== null,
            ],
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl', 30) * 60, // in seconds
            'subscription_features' => $subscriptionFeatures,
        ])->cookie($cookie);
    }

    /**
     * Panel Refresh - Refresh access token for Vue.js admin panel
     * Only allows dealer, staff, or admin roles
     */
    public function panelRefresh(Request $request): JsonResponse
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

            // Load roles for validation
            $user->load('roles');

            // Check if user is banned
            if ($user->banned ?? false) {
                return $this->error('Account is banned', null, 403);
            }

            // Check if user has allowed role for Vue.js panel (dealer, staff, or admin only)
            // Sellers are not allowed to refresh tokens via Vue.js API
            if (!$user->hasAnyRole(['dealer', 'staff', 'admin'])) {
                return $this->error('Access denied. This account type cannot access the admin panel.', null, 403);
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

            // Get subscription features for dealer/staff users (not admin)
            $subscriptionFeatures = [];
            if ($user->hasAnyRole(['dealer', 'staff']) && !$user->hasRole('admin')) {
                $dealer = $user->dealer;
                
                // For staff users, get dealer from DealerStaff relationship
                if (!$dealer && $user->hasRole('staff')) {
                    $dealerStaff = DealerStaff::where('user_id', $user->id)->first();
                    if ($dealerStaff) {
                        $dealer = $dealerStaff->dealer;
                    }
                }
                
                if ($dealer) {
                    $subscriptionFeatures = $this->subscriptionFeatureService->getFeatures($dealer);
                }
            }

            return $this->success([
                'access_token' => $newAccessToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl', 30) * 60, // in seconds
                'subscription_features' => $subscriptionFeatures,
            ])->cookie($cookie);
        } catch (JWTException $e) {
            return $this->error('Refresh token expired or invalid', null, 401);
        } catch (\Exception $e) {
            return $this->error('Failed to refresh token', null, 500);
        }
    }

    /**
     * Staff Login - Authenticate staff member using username
     * Staff members login with auto-generated username instead of email
     */
    public function staffLogin(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string|max:150',
            'password' => 'required|string|max:128',
        ]);

        // Find user by username
        $user = User::where('username', $credentials['username'])->first();

        if (!$user) {
            return $this->error('Invalid credentials', [
                'username' => ['These credentials do not match our records.'],
            ], 401);
        }

        // Verify password
        if (!Hash::check($credentials['password'], $user->password)) {
            return $this->error('Invalid credentials', [
                'username' => ['These credentials do not match our records.'],
            ], 401);
        }

        // Verify user belongs to a dealer (has DealerStaff record)
        $dealerStaff = DealerStaff::where('user_id', $user->id)->first();
        if (!$dealerStaff) {
            return $this->error('Invalid account type', [
                'username' => ['This account is not associated with any dealer.'],
            ], 403);
        }

        // Check if user is banned
        if ($user->banned ?? false) {
            return $this->error('Account is banned', [
                'ban_reason' => $user->ban_reason ?? null,
                'ban_expires' => $user->ban_expires ?? null,
            ], 403);
        }

        // Load roles
        $user->load('roles');

        // Generate JWT token
        $token = auth('api')->login($user);

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
                'username' => $user->username,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'emailVerified' => $user->email_verified_at !== null,
            ],
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl', 30) * 60, // in seconds
        ])->cookie($cookie);
    }
}

