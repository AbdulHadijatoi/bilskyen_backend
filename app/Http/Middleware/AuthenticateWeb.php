<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWeb
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Handle an incoming request.
     * Redirects to login if user is not authenticated
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return redirect('/auth/login')->with('error', 'Please login to access this page.');
        }

        // Check if user is banned
        if ($user->banned) {
            return redirect('/auth/login')->with('error', 'Account is banned. ' . ($user->ban_reason ? 'Reason: ' . $user->ban_reason : ''));
        }

        return $next($request);
    }
}

