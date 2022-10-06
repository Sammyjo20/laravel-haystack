<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Casts;

use Closure;
use InvalidArgumentException;
use Laravel\SerializableClosure\SerializableClosure;
use Sammyjo20\LaravelHaystack\Helpers\ClosureHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class SerializeClosures implements CastsAttributes
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
    public function get($model, string $key, $value, array $attributes): ?array
    {
        if (blank($value)) {
            return null;
        }

        $closures = unserialize($value, ['allowed_classes' => true]);

        if (! is_array($closures)) {
            return null;
        }

        return array_map(function (string $closure) {
            return unserialize($closure, ['allowed_classes' => true]);
        }, $closures);
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
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_array($value) === false) {
            throw new InvalidArgumentException('Value provided must be an array of serialize closures.');
        }

        // Serialize every closure

        $serialized = array_map(function (SerializableClosure $closure) {
            return serialize($closure);
        }, $value);

        return serialize($serialized);
    }
}
