<?php

namespace Sammyjo20\LaravelJobStack\Data;

use Illuminate\Contracts\Queue\ShouldQueue;
use Sammyjo20\LaravelJobStack\Models\JobStackRow;

class NextJob
{
    /**
     * Constructor
     *
     * @param  ShouldQueue  $job
     * @param  JobStackRow  $jobStackRow
     */
    public function __construct(
        readonly public ShouldQueue $job,
        readonly public JobStackRow $jobStackRow,
    ) {
        //
    }
}
