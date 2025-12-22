<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService
{
    /**
     * Get authenticated user from JWT token in cookie
     *
     * @param Request $request
     * @return User|null
     */
    public function getAuthenticatedUser(Request $request): ?User
    {
        try {
            $token = $request->cookie('access_token');
            if (!$token) {
                return null;
            }

            $user = JWTAuth::setToken($token)->authenticate();
            return $user;
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

