<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission, string $action = null): Response
    {
        // For API requests, use auth('api')->user() since auth:api middleware already authenticated
        // For web requests, use $request->user()
        if ($request->expectsJson() || $request->is('api/*')) {
            $user = auth('api')->user();
        } else {
            $user = $request->user();
        }

        if (!$user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You must be signed in to access this resource.',
                    'error_code' => 'UNAUTHORIZED',
                ], 401);
            }
            return redirect()->route('login')->with('error', 'You must be signed in to access this resource.');
        }

        $permissionString = $action ? "{$permission}.{$action}" : $permission;
        if (!$user->can($permissionString)) {
            $actionText = $action ? "{$action} {$permission}s" : "access {$permission}s";
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => "You do not have permission to {$actionText}. Please contact your administrator if you believe this is an error.",
                    'error_code' => 'FORBIDDEN',
                ], 403);
            }
            abort(403, "You do not have permission to {$actionText}");
        }

        return $next($request);
    }
}

