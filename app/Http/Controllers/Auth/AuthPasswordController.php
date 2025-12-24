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
            return $this->validationError($validator->errors());
        }

        // Verify current password
        if (!Hash::check($request->password, $user->password)) {
            return $this->error('Current password is incorrect', null, 401);
        }

        // Update password
        $user->password = $request->newPassword;
        $user->save();

        // Note: With JWT, we cannot revoke other sessions without a token blacklist.
        // The revokeOtherSessions parameter is kept for API compatibility but doesn't
        // have effect unless JWT blacklist is configured.
        // Client should re-authenticate after password change for security.

        return $this->success(['message' => 'Password changed successfully']);
    }
}

