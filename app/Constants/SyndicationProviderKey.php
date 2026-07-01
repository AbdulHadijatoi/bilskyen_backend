<?php

namespace App\Constants;

class SyndicationProviderKey
{
    public const GENERIC_JSON = 'generic_json';

    public const GENERIC_XML = 'generic_xml';

    public const FACEBOOK_CATALOG = 'facebook_catalog';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::GENERIC_JSON,
            self::GENERIC_XML,
            self::FACEBOOK_CATALOG,
        ];
    }
}
