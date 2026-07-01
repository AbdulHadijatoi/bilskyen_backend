<?php

namespace App\Constants;

class AiGenerationTask
{
    public const VEHICLE_DESCRIPTION = 'vehicle_description';

    public const VEHICLE_TITLE = 'vehicle_title';

    public const VEHICLE_HIGHLIGHTS = 'vehicle_highlights';

    public const SEO_META = 'seo_meta';

    public const ENQUIRY_REPLY = 'enquiry_reply';

    public const LEAD_SUMMARY = 'lead_summary';

    public const LISTING_DESCRIPTION = 'listing_description';

    public const CMS_REWRITE = 'cms_rewrite';

    public static function values(): array
    {
        return [
            self::VEHICLE_DESCRIPTION,
            self::VEHICLE_TITLE,
            self::VEHICLE_HIGHLIGHTS,
            self::SEO_META,
            self::ENQUIRY_REPLY,
            self::LEAD_SUMMARY,
            self::LISTING_DESCRIPTION,
            self::CMS_REWRITE,
        ];
    }

    public static function isValid(string $task): bool
    {
        return in_array($task, self::values(), true);
    }
}
