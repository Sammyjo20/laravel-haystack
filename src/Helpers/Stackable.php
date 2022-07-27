<?php

namespace Sammyjo20\LaravelHaystack\Helpers;

use Illuminate\Contracts\Queue\ShouldQueue;
use Sammyjo20\LaravelHaystack\Concerns\Stackable as StackableTrait;

class Stackable
{
    /**
     * Check if a job is stackable.
     *
     * @param  ShouldQueue  $job
     * @return bool
     */
    public static function isStackable(ShouldQueue $job): bool
    {
        return in_array(StackableTrait::class, class_uses_recursive($job), true);
    }

    /**
     * Check if a job is not stackable.
     *
     * @param  ShouldQueue  $job
     * @return bool
     */
    public static function isNotStackable(ShouldQueue $job): bool
    {
        return ! static::isStackable($job);
    }
}
