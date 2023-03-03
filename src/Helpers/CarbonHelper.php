<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Helpers;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class CarbonHelper
{
    /**
     * Create a date from seconds or from Carbon.
     */
    public static function createFromSecondsOrCarbon(int|CarbonInterface $value): CarbonImmutable
    {
        return is_int($value) ? CarbonImmutable::now()->addSeconds($value) : $value->toImmutable();
    }
}
