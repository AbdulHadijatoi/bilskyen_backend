<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Internal Auth Register Controller
 * Handles user registration endpoint
 * Called by AuthController facade
 */
class AuthRegisterController extends Controller
{
    public function __construct(
        private RolePermissionService $rolePermissionService
    ) {}

    /**
     * Register a new user with JWT authentication
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
            ],
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string',
            'roles' => 'nullable|array',
            'roles.*' => 'string|in:user,dealer,admin',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Create user with password (standard Laravel approach)
        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email), // Normalize email
            'password' => $request->password, // Will be automatically hashed by the model cast
            'phone' => $request->phone,
            'status_id' => \App\Constants\UserStatus::ACTIVE, // Default to active
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
        return $this->created([
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
}

