<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Data;

class PendingData
{
    /**
     * Constructor
     */
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly ?string $cast,
    ) {
        //
    }
}
