<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class SerializeJob implements CastsAttributes
{
    /**
     * Unserialize a job.
     *
     * @param $model
     * @param  string  $key
     * @param $value
     * @param  array  $attributes
     * @return mixed|null
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return isset($value) ? unserialize($value, ['allowed_classes' => true]) : null;
    }

    /**
     * Serialize a job.
     *
     * @param $model
     * @param  string  $key
     * @param $value
     * @param  array  $attributes
     * @return mixed|string|null
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if (blank($value)) {
            return null;
        }

        return serialize($value);
    }
}
