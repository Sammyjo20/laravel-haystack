<?php

namespace Sammyjo20\LaravelJobStack\Actions;

use Illuminate\Queue\Events\JobProcessed;

class ProcessCompletedJob
{
    /**
     * Constructor
     *
     * @param JobProcessed $jobProcessed
     */
    public function __construct(protected JobProcessed $jobProcessed)
    {
        //
    }

    public function execute(): void
    {
        //
    }
}
