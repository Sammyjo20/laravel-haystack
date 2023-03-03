<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Casts;

use InvalidArgumentException;
use Sammyjo20\LaravelHaystack\Data\MiddlewareCollection;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MiddlewareCollectionCast implements CastsAttributes
{
    /**
     * Unserialize a job.
     *
     * @return mixed|null
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return isset($value) ? unserialize($value, ['allowed_classes' => true]) : null;
    }

    /**
     * Serialize a job.
     *
     * @return mixed|string|null
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if (blank($value)) {
            return null;
        }

        if (! $value instanceof MiddlewareCollection) {
            throw new InvalidArgumentException(sprintf('Value provided must be an instance of %s.', MiddlewareCollection::class));
        }

        return serialize($value);
    }
}
