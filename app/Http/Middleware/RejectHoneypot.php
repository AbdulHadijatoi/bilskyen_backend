<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RejectHoneypot
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.hardening_enabled', true)) {
            return $next($request);
        }

        $field = (string) config('security.honeypot.field', 'website');
        $value = $request->input($field);

        if (is_string($value) && trim($value) !== '') {
            Log::info('security.honeypot.hit', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'field' => $field,
            ]);

            // Silent success for bots (do not tip them off on web forms).
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'failed' => false,
                    'message' => 'OK',
                    'data' => null,
                    'errors' => [],
                ], 200);
            }

            return back()->with('status', 'OK');
        }

        return $next($request);
    }
}
