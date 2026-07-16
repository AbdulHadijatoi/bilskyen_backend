<?php

/**
 * Resolve the Vue dealer/admin panel origin.
 * Prefer VUE_PANEL_URL / FRONTEND_URL; otherwise derive panel.{host} from APP_URL.
 * Localhost is only used for local APP_URL / APP_ENV=local.
 */
$resolvePanelUrl = static function (): string {
    $explicit = env('VUE_PANEL_URL') ?: env('FRONTEND_URL');
    if (is_string($explicit) && $explicit !== '') {
        return rtrim($explicit, '/');
    }

    $appUrl = rtrim((string) env('APP_URL', 'http://localhost'), '/');
    $host = parse_url($appUrl, PHP_URL_HOST) ?: '';
    $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';

    if (
        $host === ''
        || $host === 'localhost'
        || $host === '127.0.0.1'
        || env('APP_ENV') === 'local'
    ) {
        return 'http://localhost:5173';
    }

    if (! str_starts_with($host, 'panel.')) {
        return $scheme.'://panel.'.$host;
    }

    return $appUrl;
};

return [

    'panel_url' => $resolvePanelUrl(),

    'currency' => strtolower(env('PAYMENT_CURRENCY', 'dkk')),

    'stripe_webhook_path' => '/api/v1/webhooks/stripe',

];
