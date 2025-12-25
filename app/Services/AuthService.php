<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService
{
    /**
     * Get authenticated user from JWT token
     * Checks Authorization header first (for API requests), then falls back to cookie (for web requests)
     *
     * @param Request $request
     * @return User|null
     */
    public function getAuthenticatedUser(Request $request): ?User
    {
        try {
            // First, try to get token from Authorization header (for API requests)
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
                $user = JWTAuth::setToken($token)->authenticate();
                if ($user) {
                    return $user;
                }
            }

            // Fall back to cookie (for web requests)
            $token = $request->cookie('access_token');
            if ($token) {
                $user = JWTAuth::setToken($token)->authenticate();
                return $user;
            }

            return null;
        } catch (JWTException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if user is authenticated
     *
     * @param Request $request
     * @return bool
     */
    public function isAuthenticated(Request $request): bool
    {
        return $this->getAuthenticatedUser($request) !== null;
    }
}

