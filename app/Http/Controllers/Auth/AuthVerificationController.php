<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthVerificationController extends Controller
{
    public function __construct(
        private AuthVerificationService $authVerificationService,
    ) {}

    public function signInMagicLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|string|email|max:255',
        ]);

        $this->authVerificationService->requestMagicLink($data['email'], true);

        return $this->success([
            'message' => __('messages.messages.magic_link_sent'),
        ]);
    }

    public function verifyMagicLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
        ]);

        $result = $this->authVerificationService->verifyMagicLinkToken($data['token']);

        if (! $result) {
            return $this->error(__('messages.errors.invalid_expired_magic_link'), null, 422);
        }

        $user = $result['user'];

        if ($user->banned ?? false) {
            return $this->error(__('messages.errors.account_banned_short'), null, 403);
        }

        $accessToken = auth('api')->login($user);
        $refreshToken = JWTAuth::customClaims(['type' => 'refresh'])->fromUser($user);

        $cookie = cookie(
            'refresh_token',
            $refreshToken,
            20160,
            null,
            null,
            true,
            true,
            false,
            'Strict'
        );

        $user->load('roles');

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'emailVerified' => $user->email_verified_at !== null,
            ],
            'access_token' => $accessToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl', 30) * 60,
        ])->cookie($cookie);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $user = $this->authVerificationService->verifyEmailByToken(
            $data['token'],
            $data['user_id'] ?? null
        );

        if (! $user) {
            return $this->error(__('messages.errors.invalid_expired_verification_link'), null, 422);
        }

        return $this->success([
            'message' => __('messages.messages.email_verified_successfully'),
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'emailVerified' => true,
            ],
        ]);
    }

    public function changeEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|max:128',
        ]);

        $user = $request->user();
        $result = $this->authVerificationService->changeEmail($user, $data['email'], $data['password']);

        if (! $result['success']) {
            return $this->error($result['error'], null, $result['status'] ?? 422);
        }

        $updatedUser = $result['user'];
        $updatedUser->load('roles');

        return $this->success([
            'message' => __('messages.messages.email_change_verification_sent'),
            'user' => [
                'id' => $updatedUser->id,
                'name' => $updatedUser->name,
                'email' => $updatedUser->email,
                'roles' => $updatedUser->roles->pluck('name')->toArray(),
                'emailVerified' => false,
            ],
        ]);
    }
}
