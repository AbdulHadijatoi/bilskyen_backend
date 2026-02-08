<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Internal Auth Password Controller
 * Handles password change, reset, forgot password endpoints
 * Called by AuthController facade
 */
class AuthPasswordController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {}
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

        // Log audit trail (don't log password values)
        try {
            $this->auditLogService->logUpdate(
                $user,
                'User',
                $user->id,
                ['password' => '[REDACTED]'],
                ['password' => '[REDACTED]'],
                $request,
                null,
                null,
                'User password changed',
                ['user', 'password', 'security'],
                'warning'
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for password change', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success(['message' => 'Password changed successfully']);
    }

    /**
     * Request password reset (forgot password)
     * Generates a token and stores it in verifications table
     */
    public function forgetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $email = strtolower($request->email);
        $user = User::where('email', $email)->first();

        // Always return success message for security (don't reveal if email exists)
        $message = 'If that email is in our system, we\'ll send you a password reset link. Check your inbox!';

        if ($user) {
            // Generate password reset token
            $token = Str::random(64);
            
            // Store token in verifications table with 'password_reset' prefix in identifier
            $identifier = 'password_reset:' . $email;
            DB::table('verifications')->updateOrInsert(
                ['identifier' => $identifier],
                [
                    'value' => Hash::make($token),
                    'expires_at' => Carbon::now()->addHours(1), // Reset links expire in 1 hour
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );

            // TODO: Send email with reset link
            // For now, return token in response for testing (remove in production)
            // Mail::to($user)->send(new ResetPasswordMail($resetUrl));
            
            // Log audit trail
            try {
                $this->auditLogService->logCreateForGuest(
                    null,
                    'PasswordReset',
                    $user->id,
                    ['email' => $email, 'token_generated' => true],
                    $request,
                    'User',
                    $user->id,
                    'Password reset requested',
                    ['user', 'password', 'security'],
                    'info'
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to create audit log for password reset request', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Return token in response for testing (remove in production and send via email)
            return $this->success([
                'message' => $message,
                'token' => $token, // Remove this in production
            ]);
        }

        return $this->success(['message' => $message]);
    }

    /**
     * Reset password with token
     * Validates token and updates user password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email|max:255',
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

        $email = strtolower($request->email);
        $token = $request->token;
        $identifier = 'password_reset:' . $email;

        // Check if token exists and is valid
        $resetRecord = DB::table('verifications')
            ->where('identifier', $identifier)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$resetRecord || !Hash::check($token, $resetRecord->value)) {
            return $this->error('Invalid or expired reset token.', [
                'token' => ['The reset token is invalid or has expired.'],
            ], 400);
        }

        // Update user password
        $user = User::where('email', $email)->first();
        if (!$user) {
            return $this->error('User not found.', [
                'email' => ['The email address was not found.'],
            ], 404);
        }

        // Store before state for audit log
        $beforeData = ['password' => '[REDACTED]'];

        $user->password = $request->password;
        $user->save();

        // Delete the reset token
        DB::table('verifications')->where('identifier', $identifier)->delete();

        // Log audit trail
        try {
            $this->auditLogService->logUpdate(
                $user,
                'User',
                $user->id,
                $beforeData,
                ['password' => '[REDACTED]'],
                $request,
                null,
                null,
                'User password reset via token',
                ['user', 'password', 'security'],
                'warning'
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for password reset', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success(['message' => 'Password has been reset successfully. You can now login with your new password.']);
    }
}

