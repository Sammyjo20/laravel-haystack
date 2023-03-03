<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Tests\Fixtures\DataObjects;

class Repository
{
    /**
     * Constructor
     *
     * @param string $name
     * @param int $stars
     * @param bool $isLaravel
     */
    public function __construct(
        public readonly string $name,
        public readonly int $stars,
        public readonly bool $isLaravel,
    )
    {
        //
    }
}
