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
        $user = $this->authService->getAuthenticatedUser($request);

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        // Check if user has any of the required permissions
        if (!$user->hasAnyPermission($permissions)) {
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
                'error' => 'Unauthenticated',
                'name' => 'Unauthorized',
                'message' => 'You must be signed in to access this resource.',
                'status' => 401,
                'statusText' => 'Unauthorized',
            ], 401);
        }

        return redirect()->route('login')->with('error', 'You must be signed in to access this resource.');
    }

    /**
     * Return forbidden response
     */
    private function forbiddenResponse(array $permissions): Response
    {
        $permissionsString = implode(', ', $permissions);
        
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'error' => 'Forbidden',
                'name' => 'Forbidden',
                'message' => "You do not have the required permission(s): {$permissionsString}. Please contact your administrator if you believe this is an error.",
                'status' => 403,
                'statusText' => 'Forbidden',
            ], 403);
        }

        abort(403, "You do not have the required permission(s): {$permissionsString}");
    }
}

