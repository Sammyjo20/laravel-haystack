<?php

namespace Sammyjo20\LaravelJobStack\Concerns;

use Sammyjo20\LaravelJobStack\Models\JobStack;

trait Stackable
{
    /**
     * The JobStack the job has.
     *
     * @var JobStack
     */
    protected JobStack $jobStack;

    /**
     * Set the JobStack onto the job.
     *
     * @param JobStack $jobStack
     * @return $this
     */
    public function setJobStack(JobStack $jobStack): static
    {
        $this->jobStack = $jobStack;

        return $this;
    }

    // Todo: Add methods that proxy to the JobStack class.
}
