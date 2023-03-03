<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Data;

class HaystackOptions
{
    /**
     * Return Haystack Data On Finish
     */
    public bool $returnDataOnFinish = true;

    /**
     * Allow Failed Jobs On The Haystack
     */
    public bool $allowFailures = false;

    /**
     * Allow additional properties to be added to Haystack options.
     */
    public function __set(string $name, $value): void
    {
        $this->$name = $value;
    }
}
