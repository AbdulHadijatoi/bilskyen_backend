<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use App\Support\InternalRedirect;
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
            return $this->redirectToLogin($request, __('messages.errors.please_login_access_page'));
        }

        // Check if user is banned
        if ($user->banned) {
            $message = $user->ban_reason
                ? __('messages.errors.account_banned_with_reason', ['reason' => $user->ban_reason])
                : __('messages.errors.account_banned');
            return $this->redirectToLogin($request, $message);
        }

        return $next($request);
    }

    private function redirectToLogin(Request $request, string $error): Response
    {
        $intended = InternalRedirect::intendedFromRequest($request);
        if ($intended) {
            $request->session()->put('url.intended', $intended);
        }

        $login = '/auth/login';
        if ($intended) {
            $login .= '?return_url='.rawurlencode($intended);
        }

        return redirect($login)->with('error', $error);
    }
}

