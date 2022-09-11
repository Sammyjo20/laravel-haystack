<?php

namespace Sammyjo20\LaravelHaystack\Data;

class PendingData
{
    /**
     * Constructor
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $cast
     */
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly ?string $cast,
    )
    {
        //
    }
}
