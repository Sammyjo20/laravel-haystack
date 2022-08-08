<?php

namespace Sammyjo20\LaravelHaystack\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
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
     *
     * @throws StackableException
     */
    public function nextJob(int|CarbonInterface $delayInSecondsOrCarbon = null): static;

    /**
     * Dispatch the next bale in the haystack. Yee-haw!
     *
     * @param  int|CarbonInterface|null  $delayInSecondsOrCarbon
     * @return $this
     */
    public function nextBale(int|CarbonInterface $delayInSecondsOrCarbon = null): static;

    /**
     * Release the job for haystack to process later.
     *
     * @param  int|CarbonInterface  $delayInSecondsOrCarbon
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
     * Append a job to the end of the Haystack.
     *
     * @param StackableJob $job
     * @param int $delayInSeconds
     * @param string|null $queue
     * @param string|null $connection
     * @return $this
     */
    public function appendToHaystack(StackableJob $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): static;

    /**
     * Set the next job to run on the Haystack.
     *
     * @param  StackableJob  $job
     * @param  int  $delayInSeconds
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return $this
     */
    public function appendToHaystackNext(StackableJob $job, int $delayInSeconds = 0, string $queue = null, string $connection = null): static;

    /**
     * Get the haystack bale id
     *
     * @return int
     */
    public function getHaystackBaleId(): int;

    /**
     * Set the Haystack bale ID.
     *
     * @param  int  $haystackBaleId
     * @return $this
     */
    public function setHaystackBaleId(int $haystackBaleId): static;

    /**
     * Pause the haystack.
     *
     * @param  int|CarbonInterface  $delayInSecondsOrCarbon
     * @return $this
     */
    public function pauseHaystack(int|CarbonInterface $delayInSecondsOrCarbon): static;

    /**
     * Set data on the haystack.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  string|null  $cast
     * @return $this
     */
    public function setHaystackData(string $key, mixed $value, string $cast = null): static;

    /**
     * Get data on the haystack.
     *
     * @param  string  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public function getHaystackData(string $key, mixed $default = null): mixed;

    /**
     * Get all data on the haystack.
     *
     * @return mixed
     */
    public function allHaystackData(): Collection;

    /**
     * Get the haystack bale attempts.
     *
     * @return int
     */
    public function getHaystackBaleAttempts(): int;

    /**
     * Set the haystack bale attempts.
     *
     * @param  int  $attempts
     * @return $this
     */
    public function setHaystackBaleAttempts(int $attempts): static;
}
