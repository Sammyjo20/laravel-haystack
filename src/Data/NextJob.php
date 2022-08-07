<?php

namespace Sammyjo20\LaravelHaystack\Data;

use Sammyjo20\LaravelHaystack\Models\HaystackBale;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class NextJob
{
    /**
     * Constructor
     *
     * @param  StackableJob  $job
     * @param  HaystackBale  $haystackRow
     */
    public function __construct(
        readonly public StackableJob $job,
        readonly public HaystackBale $haystackRow,
    ) {
        //
    }
}
