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
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
                'name' => 'Unauthorized',
                'message' => 'You must be signed in to access this resource.',
                'status' => 401,
                'statusText' => 'Unauthorized',
            ], 401);
        }

        $permissionString = $action ? "{$permission}.{$action}" : $permission;
        if (!$user->can($permissionString)) {
            return response()->json([
                'error' => "You do not have permission to {$action} {$permission}s. Please contact your administrator if you believe this is an error.",
                'name' => 'Forbidden',
                'message' => "You do not have permission to {$action} {$permission}s. Please contact your administrator if you believe this is an error.",
                'status' => 403,
                'statusText' => 'Forbidden',
            ], 403);
        }

        return $next($request);
    }
}

