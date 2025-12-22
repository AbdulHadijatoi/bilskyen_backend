<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class ViewHelper
{
    /**
     * Check if current route matches the given route pattern
     *
     * @param string $route
     * @param Request|null $request
     * @return bool
     */
    public static function routeIsActive(string $route, ?Request $request = null): bool
    {
        $request = $request ?? request();
        return str_starts_with($request->path(), $route);
    }

    /**
     * Generate breadcrumb data structure from segments
     *
     * @param string $base Base path (e.g., '/dealer')
     * @param array $segments Route segments (e.g., ['vehicles', 'overview'])
     * @return array Array of breadcrumb items with 'label' and 'href' keys
     */
    public static function generateBreadcrumbs(string $base, array $segments): array
    {
        $breadcrumbs = [];
        $path = $base;

        foreach ($segments as $index => $segment) {
            $path .= '/' . $segment;
            $breadcrumbs[] = [
                'label' => self::formatBreadcrumbLabel($segment),
                'href' => $path,
                'isLast' => $index === count($segments) - 1,
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Format segment name for display
     * Converts kebab-case to Title Case
     *
     * @param string $segment
     * @return string
     */
    public static function formatBreadcrumbLabel(string $segment): string
    {
        return ucwords(str_replace('-', ' ', $segment));
    }
}

