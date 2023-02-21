<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Casts;

use Sammyjo20\LaravelHaystack\Helpers\SerializationHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class Serialized implements CastsAttributes
{
    /**
     * Unserialize a job.
     *
     * @return mixed|null
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return isset($value) ? SerializationHelper::unserialize($value, ['allowed_classes' => true]) : null;
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

        return SerializationHelper::serialize($value);
    }
}
