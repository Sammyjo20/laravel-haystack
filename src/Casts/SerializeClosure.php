<?php

namespace Sammyjo20\LaravelHaystack\Casts;

use Closure;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;
use Laravel\SerializableClosure\SerializableClosure;

class SerializeClosure implements CastsAttributes
{
    /**
     * Unserialize a closure.
     *
     * @param $model
     * @param string $key
     * @param $value
     * @param array $attributes
     * @return mixed|null
     */
    public function get($model, string $key, $value, array $attributes): ?Closure
    {
        return isset($value) ? unserialize($value, ['allowed_classes' => true])->getClosure() : null;
    }

    /**
     * Serialize a closure.
     *
     * @param $model
     * @param string $key
     * @param $value
     * @param array $attributes
     * @return mixed|string|null
     * @throws \Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof Closure === false && is_callable($value) === false) {
            throw new InvalidArgumentException('Value provided must be a closure or an invokable class.');
        }

        if (is_callable($value) === true && is_object($value) === false) {
            throw new InvalidArgumentException('Callable value provided must be an invokable class.');
        }

        $closure = $value instanceof Closure ? $value : static fn() => $value();

        return serialize(new SerializableClosure($closure));
    }
}
