<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Helpers;

use InvalidArgumentException;

class DataValidator
{
    /**
     * Throw an exception if the cast is invalid for the data type.
     */
    public static function validateCast(mixed $value, ?string $cast = null): void
    {
        if (is_null($cast) && is_string($value) === false && is_int($value) === false) {
            throw new InvalidArgumentException('You must specify a cast if the value is not a string or integer.');
        }
    }
}
