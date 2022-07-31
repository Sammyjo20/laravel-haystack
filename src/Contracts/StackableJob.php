<?php

namespace Sammyjo20\LaravelHaystack\Contracts;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Exceptions\StackableException;

interface StackableJob
{
    /**
     * Get the job stack.
     *
     * @return Haystack
     */
    public function getHaystack(): Haystack;

    /**
     * Set the Haystack onto the job.
     *
     * @param  Haystack  $haystack
     * @return $this
     */
    public function setHaystack(Haystack $haystack): static;

    /**
     * Dispatch the next job in the Haystack.
     *
     * @return $this
     * @throws StackableException
     */
    public function nextJob(int|CarbonInterface $delayInSecondsOrCarbon = null): static;

    /**
     * Dispatch the next bale in the haystack. Yee-haw!
     *
     * @return $this
     * @throws StackableException
     */
    public function nextBale(): static;

    /**
     * Release the job for haystack to process later.
     *
     * @param int|CarbonInterface $delayInSecondsOrCarbon
     * @return $this
     */
    public function longRelease(int|CarbonInterface $delayInSecondsOrCarbon): static;

    /**
     * Finish the Haystack.
     *
     * @return $this
     */
    public function finishHaystack(): static;

    /**
     * Fail the job stack.
     *
     * @return $this
     */
    public function failHaystack(): static;

    /**
     * Append a job to the Haystack.
     *
     * @param  ShouldQueue  $job
     * @param  int  $delayInSeconds
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return $this
     */
    public function appendToHaystack(ShouldQueue $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): static;

    /**
     * Get the haystack bale id
     *
     * @return int
     */
    public function getHaystackBaleId(): int;

    /**
     * Set the Haystack bale ID.
     *
     * @param int $haystackBaleId
     * @return $this
     */
    public function setHaystackBaleId(int $haystackBaleId): static;
}
