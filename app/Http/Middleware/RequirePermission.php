<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AuthService;

class RequirePermission
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Handle an incoming request.
     * 
     * Usage: ->middleware('permission:edit.vehicles') or ->middleware('permission:edit.vehicles,delete.vehicles')
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions  One or more permission names
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        // For API requests, use auth('api')->user() since auth:api middleware already authenticated
        // For web requests, fall back to AuthService which checks cookies
        if ($request->expectsJson() || $request->is('api/*')) {
            $user = auth('api')->user();
        } else {
            $user = $this->authService->getAuthenticatedUser($request);
        }

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        // Check if user has any of the required permissions (dealer or staff equivalent)
        $expanded = [];
        foreach ($permissions as $permission) {
            $expanded[] = $permission;
            if (str_starts_with($permission, 'dealer.')) {
                $expanded[] = 'staff.'.substr($permission, 7);
            }
        }

        if (!$user->hasAnyPermission(array_unique($expanded))) {
            return $this->forbiddenResponse($permissions);
        }

        return $next($request);
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(): Response
    {
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.must_be_signed_in'),
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        return redirect()->route('login')->with('error', __('messages.errors.must_be_signed_in'));
    }

    /**
     * Return forbidden response
     */
    private function forbiddenResponse(array $permissions): Response
    {
        $permissionsString = implode(', ', $permissions);
        
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.no_required_permissions', ['permissions' => $permissionsString]),
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        abort(403, __('messages.errors.no_required_permissions', ['permissions' => $permissionsString]));
    }
}

