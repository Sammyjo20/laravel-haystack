<?php

namespace Sammyjo20\LaravelHaystack\Concerns;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Sammyjo20\LaravelHaystack\Helpers\CarbonHelper;
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
     * The ID of the haystack "bale". Used for deleting.
     *
     * @var int
     */
    protected int $haystackBaleId;

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
     * @throws StackableException
     */
    public function nextJob(int|CarbonInterface $delayInSecondsOrCarbon = null): static
    {
        if (config('haystack.process_automatically', false) === true) {
            throw new StackableException('The "nextJob" method is unavailable when "haystack.process_automatically" is enabled.');
        }

        $this->haystack->dispatchNextJob($this, $delayInSecondsOrCarbon);

        return $this;
    }

    /**
     * Dispatch the next bale in the haystack. Yee-haw!
     *
     * @return $this
     * @throws StackableException
     */
    public function nextBale(): static
    {
        return $this->nextJob();
    }

    /**
     * Release the job for haystack to process later.
     *
     * @param int|CarbonInterface $delayInSecondsOrCarbon
     * @return $this
     */
    public function longRelease(int|CarbonInterface $delayInSecondsOrCarbon): static
    {
        $resumeAt = CarbonHelper::createFromSecondsOrCarbon($delayInSecondsOrCarbon);

        $this->haystack->pause($resumeAt);

        return $this;
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

    /**
     * Get the haystack bale id
     *
     * @return int
     */
    public function getHaystackBaleId(): int
    {
        return $this->haystackBaleId;
    }

    /**
     * Set the Haystack bale ID.
     *
     * @param int $haystackBaleId
     * @return $this
     */
    public function setHaystackBaleId(int $haystackBaleId): static
    {
        $this->haystackBaleId = $haystackBaleId;

        return $this;
    }
}
