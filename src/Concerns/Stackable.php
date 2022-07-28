<?php

namespace Sammyjo20\LaravelHaystack\Concerns;

use Illuminate\Contracts\Queue\ShouldQueue;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Exceptions\StackableException;

trait Stackable
{
    /**
     * The Haystack the job has.
     *
     * @var Haystack
     */
    protected Haystack $haystack;

    /**
     * Get the job stack.
     *
     * @return Haystack
     */
    public function getHaystack(): Haystack
    {
        return $this->haystack;
    }

    /**
     * Set the Haystack onto the job.
     *
     * @param  Haystack  $haystack
     * @return $this
     */
    public function setHaystack(Haystack $haystack): static
    {
        $this->haystack = $haystack;

        return $this;
    }

    /**
     * Dispatch the next job in the Haystack.
     *
     * @return $this
     */
    public function nextJob(): static
    {
        if (config('haystack.process_automatically', false) === true) {
            throw new StackableException('The "nextJob" method is unavailable when "haystack.process_automatically" is enabled.');
        }

        $this->haystack->dispatchNextJob();

        return $this;
    }

    /**
     * Dispatch the next bale in the haystack. Yee-haw!
     *
     * @return $this
     */
    public function nextBale(): static
    {
        return $this->nextJob();
    }

    /**
     * Finish the Haystack.
     *
     * @return $this
     */
    public function finishHaystack(): static
    {
        $this->haystack->finish();

        return $this;
    }

    /**
     * Fail the job stack.
     *
     * @return $this
     */
    public function failHaystack(): static
    {
        $this->haystack->finish(true);

        return $this;
    }

    /**
     * Append a job to the Haystack.
     *
     * @param  ShouldQueue  $job
     * @param  int  $delayInSeconds
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return $this
     */
    public function appendToHaystack(ShouldQueue $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): static
    {
        $this->haystack->appendJob($job, $delayInSeconds, $queue, $connection);

        return $this;
    }
}
