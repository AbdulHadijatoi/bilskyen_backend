<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('messages.errors.user_not_found'),
                    'error_code' => 'UNAUTHENTICATED',
                ], 404);
            }

            // Check if user is banned
            if ($user->banned) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('messages.errors.account_banned_short'),
                    'error_code' => 'FORBIDDEN',
                ], 403);
            }
        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.token_expired'),
                'error_code' => 'TOKEN_EXPIRED',
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.token_invalid'),
                'error_code' => 'TOKEN_INVALID',
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.token_not_provided'),
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        return $next($request);
    }
}

