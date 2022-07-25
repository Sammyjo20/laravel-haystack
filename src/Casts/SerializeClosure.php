<?php

namespace Sammyjo20\LaravelJobStack\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Laravel\SerializableClosure\SerializableClosure;

class SerializeClosure implements CastsAttributes
{
    /**
     * Unserialize a closure.
     *
     * @param $model
     * @param  string  $key
     * @param $value
     * @param  array  $attributes
     * @return mixed|null
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return isset($value) ? unserialize($value, ['allowed_classes' => true])->getClosure() : null;
    }

    /**
     * Serialize a closure.
     *
     * @param $model
     * @param  string  $key
     * @param $value
     * @param  array  $attributes
     * @return mixed|string|null
     *
     * @throws \Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if (blank($value)) {
            return null;
        }

        return serialize(new SerializableClosure($value));
    }
}
