<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Internal Auth Password Controller
 * Handles password change, reset, forgot password endpoints
 * Called by AuthController facade
 */
class AuthPasswordController extends Controller
{
    /**
     * Change password
     * Note: Admins cannot use this endpoint for security reasons.
     * Admins must use the admin-specific endpoint.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        // Security: Prevent admins from using this endpoint
        if ($user->hasRole('admin')) {
            return $this->error('Admins must use the admin-specific password change endpoint', null, 403);
        }

        // Match frontend API format: current_password, password, password_confirmation
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|max:128',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
            ],
            'password_confirmation' => 'required|string|same:password',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect', [
                'current_password' => ['The current password is incorrect.'],
            ], 401);
        }

        // Update password
        $user->password = $request->password;
        $user->save();

        return $this->success(['message' => 'Password changed successfully']);
    }
}

