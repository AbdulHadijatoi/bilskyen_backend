<?php

namespace App\Helpers;

use Carbon\Carbon;

class FormatHelper
{
    /**
     * Format currency value
     *
     * @param float|null $amount
     * @param string|null $currency Currency code (default: DKK)
     * @return string
     */
    public static function formatCurrency(?float $amount, ?string $currency = 'DKK'): string
    {
        // Handle null amount
        if ($amount === null) {
            return 'N/A';
        }

        if ($currency === 'DKK') {
            // Format Danish Krone with dot as thousands separator and comma as decimal separator
            return number_format($amount, 0, ',', '.') . ' kr.';
        }

        // For other currencies, use standard formatting
        return number_format($amount, 2, '.', ',') . ' ' . $currency;
    }

    /**
     * Format phone number
     * Currently returns as-is, can be enhanced for specific formats
     *
     * @param string $phone
     * @return string
     */
    public static function formatPhoneNumber(string $phone): string
    {
        // Remove any non-digit characters except + at the start
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        
        // If it starts with +, keep it
        if (str_starts_with($cleaned, '+')) {
            return $cleaned;
        }

        return $cleaned;
    }

    /**
     * Format date consistently
     *
     * @param Carbon|string $date
     * @param string|null $format Date format (default: 'Y-m-d')
     * @return string
     */
    public static function formatDate(Carbon|string $date, ?string $format = 'Y-m-d'): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date->format($format);
    }

    /**
     * Format date for display (human-readable format)
     *
     * @param Carbon|string $date
     * @return string
     */
    public static function formatDateDisplay(Carbon|string $date): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date->format('M d, Y');
    }

    /**
     * Format datetime consistently
     *
     * @param Carbon|string $dateTime
     * @param string|null $format DateTime format (default: 'Y-m-d H:i:s')
     * @return string
     */
    public static function formatDateTime(Carbon|string $dateTime, ?string $format = 'Y-m-d H:i:s'): string
    {
        if (is_string($dateTime)) {
            $dateTime = Carbon::parse($dateTime);
        }

        return $dateTime->format($format);
    }
}

