<?php

namespace Sammyjo20\LaravelHaystack\Data;

use Illuminate\Contracts\Queue\ShouldQueue;
use Sammyjo20\LaravelHaystack\Models\HaystackBale;

class NextJob
{
    /**
     * Constructor
     *
     * @param  ShouldQueue  $job
     * @param  HaystackBale  $haystackRow
     */
    public function __construct(
        readonly public ShouldQueue $job,
        readonly public HaystackBale $haystackRow,
    ) {
        //
    }
}
