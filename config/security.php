<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Hardening Feature Flag
    |--------------------------------------------------------------------------
    |
    | When false, Turnstile and honeypot checks are skipped (useful for local
    | tests). Rate limits and security headers still apply.
    |
    */
    'hardening_enabled' => (bool) env('SECURITY_HARDENING', true),

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    */
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY', ''),
        'secret_key' => env('TURNSTILE_SECRET_KEY', ''),
        // When secret is empty and hardening is on, verification fails closed in production.
        'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ],

    /*
    |--------------------------------------------------------------------------
    | Honeypot
    |--------------------------------------------------------------------------
    */
    'honeypot' => [
        'field' => 'website',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public listing page size cap
    |--------------------------------------------------------------------------
    */
    'max_public_page_size' => (int) env('SECURITY_MAX_PUBLIC_PAGE_SIZE', 48),

    /*
    |--------------------------------------------------------------------------
    | Abusive client detection
    |--------------------------------------------------------------------------
    */
    'abuse' => [
        'ban_seconds' => (int) env('SECURITY_ABUSE_BAN_SECONDS', 300),
        'score_threshold' => (int) env('SECURITY_ABUSE_SCORE_THRESHOLD', 8),
        'velocity_window_seconds' => 60,
        'velocity_max_requests' => 40,
    ],

    /*
    |--------------------------------------------------------------------------
    | Partner feed IP allowlist (comma-separated). Empty = allow any IP with token.
    |--------------------------------------------------------------------------
    */
    'feed_ip_allowlist' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SECURITY_FEED_IP_ALLOWLIST', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Security headers / CSP
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'csp_report_only' => (bool) env('SECURITY_CSP_REPORT_ONLY', true),
        'csp' => env(
            'SECURITY_CSP',
            "default-src 'self'; ".
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com; ".
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com https://cdn.jsdelivr.net; ".
            "img-src 'self' data: https: blob:; ".
            "font-src 'self' data: https://fonts.gstatic.com; ".
            "connect-src 'self' https://challenges.cloudflare.com; ".
            "frame-src https://challenges.cloudflare.com; ".
            "frame-ancestors 'self'; ".
            "base-uri 'self'; ".
            "form-action 'self'"
        ),
    ],

];
