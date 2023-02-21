<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\PostgresConnection;

class SerializationHelper
{
    /**
     * Serialize the given value.
     */
    public static function serialize(mixed $value): string
    {
        $serialized = serialize($value);

        return DB::connection() instanceof PostgresConnection
            ? base64_encode($serialized)
            : $serialized;
    }

    /**
     * Unserialize the given value.
     */
    public static function unserialize(string $serialized, array $options = []): mixed
    {
        if (DB::connection() instanceof PostgresConnection && ! Str::contains($serialized, [':', ';'])) {
            $serialized = base64_decode($serialized);
        }

        return unserialize($serialized, $options);
    }
}
