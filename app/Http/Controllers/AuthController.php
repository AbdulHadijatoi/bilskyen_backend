<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function __construct(
        private RolePermissionService $rolePermissionService
    ) {}

    /**
     * Register a new user with JWT authentication
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        // Match frontend validation: name (2-100), email (max 255), password (8-128 with complexity)
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
                // 'regex:/[A-Z]/',      // At least one uppercase letter
                // 'regex:/[a-z]/',      // At least one lowercase letter
                // 'regex:/[0-9]/',      // At least one number
                // 'regex:/[@$!%*?&#]/', // At least one special character
            ],
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string',
            'roles' => 'nullable|array',
            'roles.*' => 'string|in:user,dealer,admin',
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&#).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create user with password (standard Laravel approach)
        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email), // Normalize email
            'password' => $request->password, // Will be automatically hashed by the model cast
            'phone' => $request->phone,
            'address' => $request->address,
            'email_verified' => false,
        ]);

        // Assign default role if no roles provided
        $roles = $request->input('roles', ['user']);
        $this->rolePermissionService->assignRoleToUser($user, $roles);

        // Load roles for response
        $user->load('roles');

        // Generate JWT access token
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

        // Match JWT login response format
        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'emailVerified' => $user->email_verified,
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl', 30) * 60, // in seconds
            ],
        ], 201)->cookie($cookie);
    }


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

        return response()->json([
            'message' => 'Signed out successfully',
        ]);
    }

    /**
     * Get current session/user
     */
    public function getSession(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('roles');

        // Match frontend expected response format (camelCase)
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'emailVerified' => $user->email_verified,
                'phone' => $user->phone,
                'address' => $user->address,
                'image' => $user->image,
                'banned' => $user->banned,
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

        return response()->json([
            'message' => 'Session revoked successfully',
        ]);
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
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update($validator->validated());
        $user->load('roles');

        // Match frontend expected response format (camelCase)
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'emailVerified' => $user->email_verified,
                'phone' => $user->phone,
                'address' => $user->address,
                'image' => $user->image,
            ],
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        // Match frontend validation requirements
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|max:128', // currentPassword
            'newPassword' => [
                'required',
                'string',
                'min:8',
                'max:128',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*?&#]/',
            ],
            'revokeOtherSessions' => 'sometimes|boolean',
        ], [
            'newPassword.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&#).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify current password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
            ], 401);
        }

        // Update password
        $user->password = $request->newPassword;
        $user->save();

        // Note: With JWT, we cannot revoke other sessions without a token blacklist.
        // The revokeOtherSessions parameter is kept for API compatibility but doesn't
        // have effect unless JWT blacklist is configured.
        // Client should re-authenticate after password change for security.

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * JWT Login - Authenticate user and receive tokens
     * 
     * @param Request $request
     * @return JsonResponse
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
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
                'errors' => [
                    'email' => ['These credentials do not match our records.'],
                ],
            ], 401);
        }

        $user = auth('api')->user();
        $user->load('roles');

        // Check if user is banned
        if ($user->banned) {
            auth('api')->logout();
            return response()->json([
                'status' => 'error',
                'message' => 'Account is banned',
                'ban_reason' => $user->ban_reason,
                'ban_expires' => $user->ban_expires,
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

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'emailVerified' => $user->email_verified,
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl', 30) * 60, // in seconds
            ],
        ])->cookie($cookie);
    }

    /**
     * JWT Refresh - Refresh access token using refresh token
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $refreshToken = $request->cookie('refresh_token');
            
            if (!$refreshToken) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Refresh token not found',
                ], 401);
            }

            // Authenticate using refresh token
            $user = JWTAuth::setToken($refreshToken)->authenticate();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                ], 404);
            }

            // Check if user is banned
            if ($user->banned) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Account is banned',
                ], 403);
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

            return response()->json([
                'status' => 'success',
                'data' => [
                    'access_token' => $newAccessToken,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl', 30) * 60, // in seconds
                ],
            ])->cookie($cookie);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Refresh token expired or invalid',
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to refresh token',
            ], 500);
        }
    }

    /**
     * JWT Logout - Logout user and invalidate tokens
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Invalidate current access token
            JWTAuth::parseToken()->invalidate();

            // Clear refresh token cookie
            $cookie = cookie()->forget('refresh_token');

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully',
            ])->cookie($cookie);
        } catch (JWTException $e) {
            // Token might already be invalid, but we still want to clear the cookie
            $cookie = cookie()->forget('refresh_token');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully',
            ])->cookie($cookie);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to logout',
            ], 500);
        }
    }

    /**
     * JWT Me - Get current authenticated user information
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $user->load('roles');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'emailVerified' => $user->email_verified,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'image' => $user->image,
                    'banned' => $user->banned,
                    'created_at' => $user->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }
    }
}

