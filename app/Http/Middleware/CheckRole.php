<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AuthService;

class CheckRole
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Handle an incoming request.
     * 
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,editor')
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  One or more role names
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
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

        // Check if user has any of the required roles
        if (!$user->hasAnyRole($roles)) {
            return $this->forbiddenResponse($roles);
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
                'message' => 'You must be signed in to access this resource.',
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        return redirect()->route('login')->with('error', 'You must be signed in to access this resource.');
    }

    /**
     * Return forbidden response
     */
    private function forbiddenResponse(array $roles): Response
    {
        $rolesString = implode(', ', $roles);
        
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'message' => "You do not have the required role(s): {$rolesString}. Please contact your administrator if you believe this is an error.",
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        abort(403, "You do not have the required role(s): {$rolesString}");
    }
}

