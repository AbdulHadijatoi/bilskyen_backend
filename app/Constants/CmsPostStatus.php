<?php

namespace App\Constants;

class CmsPostStatus
{
    public const DRAFT = 'draft';

    public const SCHEDULED = 'scheduled';

    public const PUBLISHED = 'published';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [self::DRAFT, self::SCHEDULED, self::PUBLISHED];
    }
}
