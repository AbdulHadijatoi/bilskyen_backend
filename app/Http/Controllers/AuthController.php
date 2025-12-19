<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function signUp(Request $request): JsonResponse
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
                'regex:/[A-Z]/',      // At least one uppercase letter
                'regex:/[a-z]/',      // At least one lowercase letter
                'regex:/[0-9]/',      // At least one number
                'regex:/[@$!%*?&#]/', // At least one special character
            ],
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string',
            'role' => 'nullable|string|in:user,dealer,admin',
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&#).',
        ]);

        if ($validator->fails()) {
            return response()->json([
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
            'role' => $request->role ?? 'user',
            'email_verified' => false,
        ]);

        // Create a token for the user
        $token = $user->createToken('auth-token')->plainTextToken;

        // Match frontend expected response format
        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'emailVerified' => $user->email_verified,
            ],
        ], 201);
    }

    /**
     * Sign in user
     */
    public function signIn(Request $request): JsonResponse
    {
        // Match frontend validation: email (max 255), password (max 128)
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|max:128',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find user by email (normalize to lowercase)
        $user = User::where('email', strtolower($request->email))->first();

        // Check credentials using standard Laravel password verification
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check if user is banned
        if ($user->banned) {
            return response()->json([
                'message' => 'Account is banned',
                'ban_reason' => $user->ban_reason,
                'ban_expires' => $user->ban_expires,
            ], 403);
        }

        // Delete existing tokens (optional - for single device login)
        // $user->tokens()->delete();

        // Create a new token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Match frontend expected response format
        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'emailVerified' => $user->email_verified,
            ],
        ]);
    }

    /**
     * Sign out user
     */
    public function signOut(Request $request): JsonResponse
    {
        // Delete the current token
        $request->user()->currentAccessToken()->delete();

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

        // Match frontend expected response format (camelCase)
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'emailVerified' => $user->email_verified,
                'phone' => $user->phone,
                'address' => $user->address,
                'image' => $user->image,
                'banned' => $user->banned,
            ],
        ]);
    }

    /**
     * Revoke current session
     */
    public function revokeSession(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

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

        // Match frontend expected response format (camelCase)
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
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

        // Revoke other sessions if requested
        if ($request->boolean('revokeOtherSessions', true)) {
            $currentToken = $request->user()->currentAccessToken();
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();
        }

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }
}

