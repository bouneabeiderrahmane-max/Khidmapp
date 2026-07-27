<?php

namespace App\Support;

/**
 * The four statuses confirmed by the cahier des charges (8.1): Active,
 * Inactive, En pause, En test.
 */
final class BoutiqueStatus
{
    public const ACTIVE = 'active';

    public const INACTIVE = 'inactive';

    public const EN_PAUSE = 'en_pause';

    public const EN_TEST = 'en_test';

    public static function all(): array
    {
        return [self::ACTIVE, self::INACTIVE, self::EN_PAUSE, self::EN_TEST];
    }
}
