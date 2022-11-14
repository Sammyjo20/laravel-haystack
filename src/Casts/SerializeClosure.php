<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Casts;

use Closure;
use InvalidArgumentException;
use Laravel\SerializableClosure\SerializableClosure;
use Sammyjo20\LaravelHaystack\Helpers\ClosureHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        if (!isset($value)) {
            return null;
        }

        if (DB::connection() instanceof PostgresConnection && ! Str::contains($value, [':', ';'])) {
            $value = base64_decode($value);
        }

        return unserialize($value, ['allowed_classes' => true])->getClosure();
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

        $serialized = serialize(new SerializableClosure($closure));

        return DB::connection() instanceof PostgresConnection
            ? base64_encode($serialized)
            : $serialized;
    }
}
