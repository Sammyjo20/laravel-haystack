<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Helpers;

use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SerializationHelper
{
    /**
     * Serialize the given value.
     *
     * @param  mixed  $value
     * @return string
     */
    public static function serialize(mixed $value) : string
    {
        $serialized = serialize($value);

        return DB::connection() instanceof PostgresConnection
            ? base64_encode($serialized)
            : $serialized;
    }

    /**
     * Unserialize the given value.
     *
     * @param  string  $serialized
     * @param  array   $options
     * @return mixed
     */
    public static function unserialize(string $serialized, array $options = []) : mixed
    {
        if (DB::connection() instanceof PostgresConnection && ! Str::contains($serialized, [':', ';'])) {
            $serialized = base64_decode($serialized);
        }

        return unserialize($serialized, $options);
    }
}
