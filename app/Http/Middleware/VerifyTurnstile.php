<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyTurnstile
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.hardening_enabled', true)) {
            return $next($request);
        }

        $secret = (string) config('security.turnstile.secret_key', '');

        // Local/dev without keys: skip so developers are not blocked.
        if ($secret === '' && ! app()->environment('production')) {
            return $next($request);
        }

        if ($secret === '') {
            Log::channel('stack')->warning('security.turnstile.misconfigured', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return $this->reject($request, __('messages.security.turnstile_unavailable'));
        }

        $token = (string) $request->input('cf-turnstile-response', $request->header('CF-Turnstile-Response', ''));

        if ($token === '') {
            Log::info('security.turnstile.missing', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return $this->reject($request, __('messages.security.turnstile_required'));
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post((string) config('security.turnstile.verify_url'), [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);

            $success = (bool) data_get($response->json(), 'success', false);

            if (! $success) {
                Log::info('security.turnstile.failed', [
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                    'codes' => data_get($response->json(), 'error-codes', []),
                ]);

                return $this->reject($request, __('messages.security.turnstile_failed'));
            }
        } catch (\Throwable $e) {
            Log::warning('security.turnstile.error', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'error' => $e->getMessage(),
            ]);

            return $this->reject($request, __('messages.security.turnstile_unavailable'));
        }

        return $next($request);
    }

    private function reject(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'failed' => true,
                'message' => $message,
                'data' => null,
                'errors' => ['cf-turnstile-response' => [$message]],
            ], 422);
        }

        return back()->withErrors(['cf-turnstile-response' => $message])->withInput();
    }
}
