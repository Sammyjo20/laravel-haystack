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
    public static function serialize($value)
    {
        $serialized = serialize($value);

        return $this->connection instanceof PostgresConnection
            ? base64_encode($serialized)
            : $serialized;
    }

    /**
     * Unserialize the given value.
     *
     * @param  string  $serialized
     * @return mixed
     */
    public static function unserialize($serialized)
    {
        if ($this->connection instanceof PostgresConnection && ! Str::contains($serialized, [':', ';'])) {
            $serialized = base64_decode($serialized);
        }

        return unserialize($serialized);
    }
}
