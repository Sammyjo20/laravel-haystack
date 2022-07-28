<?php

namespace Sammyjo20\LaravelHaystack\Casts;

use Closure;
use InvalidArgumentException;
use Laravel\SerializableClosure\SerializableClosure;
use Sammyjo20\LaravelHaystack\Helpers\ClosureHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

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
    public function get($model, string $key, $value, array $attributes): ?Closure
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
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof Closure === false && is_callable($value) === false) {
            throw new InvalidArgumentException('Value provided must be a closure or an invokable class.');
        }

        $closure = ClosureHelper::fromCallable($value);

        return serialize(new SerializableClosure($closure));
    }
}
