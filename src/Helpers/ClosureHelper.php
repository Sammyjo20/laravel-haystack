<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Helpers;

use Closure;
use InvalidArgumentException;

class ClosureHelper
{
    /**
     * Create a closure from a given callable.
     */
    public static function fromCallable(Closure|callable $value): Closure
    {
        if (is_callable($value) === true && is_object($value) === false) {
            throw new InvalidArgumentException('Callable value provided must be an invokable class.');
        }

        return $value instanceof Closure ? $value : static fn () => $value();
    }
}
