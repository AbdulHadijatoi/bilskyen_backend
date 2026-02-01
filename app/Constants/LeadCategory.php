<?php

namespace App\Constants;

/**
 * Lead category constants for API validation
 */
class LeadCategory
{
    public const PRICE_NEGOTIATION_REQUEST = 1;
    public const FINANCING_REQUEST = 2;
    public const WHATSAPP_CLICKED = 3;
    public const EMAIL_CLICKED = 4;
    public const ENQUIRY_FORM_SUBMISSION = 5;
    public const PHONE_NUMBER_REVEALED = 6;
    public const REQUEST_TEST_DRIVE = 7;

    /**
     * Get all valid category IDs
     */
    public static function values(): array
    {
        return [
            self::PRICE_NEGOTIATION_REQUEST,
            self::FINANCING_REQUEST,
            self::WHATSAPP_CLICKED,
            self::EMAIL_CLICKED,
            self::ENQUIRY_FORM_SUBMISSION,
            self::PHONE_NUMBER_REVEALED,
            self::REQUEST_TEST_DRIVE,
        ];
    }

    /**
     * Check if category ID is valid
     */
    public static function isValid(int $categoryId): bool
    {
        return in_array($categoryId, self::values(), true);
    }
}
