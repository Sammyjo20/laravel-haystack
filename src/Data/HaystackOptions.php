<?php

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
}

