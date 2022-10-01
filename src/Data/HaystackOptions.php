<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Data;

class HaystackOptions
{
    /**
     * Return Haystack Data On Finish
     *
     * @var bool
     */
    public bool $returnDataOnFinish = true;

    /**
     * Allow Failed Jobs On The Haystack
     *
     * @var bool
     */
    public bool $allowFailures = false;

    /**
     * Allow additional properties to be added to Haystack options.
     *
     * @param string $name
     * @param $value
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->$name = $value;
    }
}
