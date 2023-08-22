<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Exceptions\StackableException;

interface StackableJob
{
    /**
     * Get the job stack.
     */
    public function getHaystack(): Haystack;

    /**
     * Set the Haystack onto the job.
     *
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
     * @return $this
     */
    public function nextBale(int|CarbonInterface $delayInSecondsOrCarbon = null): static;

    /**
     * Release the job for haystack to process later.
     *
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
     * Append jobs to the haystack.
     *
     * @return $this
     */
    public function appendToHaystack(StackableJob|Collection|array $jobs, int $delayInSeconds = 0, string $queue = null, string $connection = null): static;

    /**
     * Prepend jobs to the haystack.
     *
     * @return $this
     */
    public function prependToHaystack(StackableJob|Collection|array $jobs, int $delayInSeconds = 0, string $queue = null, string $connection = null): static;

    /**
     * Get the haystack bale id
     */
    public function getHaystackBaleId(): int;

    /**
     * Set the Haystack bale ID.
     *
     * @return $this
     */
    public function setHaystackBaleId(int $haystackBaleId): static;

    /**
     * Pause the haystack.
     *
     * @return $this
     */
    public function pauseHaystack(int|CarbonInterface $delayInSecondsOrCarbon): static;

    /**
     * Set data on the haystack.
     *
     * @return $this
     */
    public function setHaystackData(string $key, mixed $value, string $cast = null): static;

    /**
     * Get data on the haystack.
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
     */
    public function getHaystackBaleAttempts(): int;

    /**
     * Set the haystack bale attempts.
     *
     * @return $this
     */
    public function setHaystackBaleAttempts(int $attempts): static;

    /**
     * Get the haystack bale retry-until.
     */
    public function getHaystackBaleRetryUntil(): ?int;

    /**
     * Set the haystack bale retry-until.
     */
    public function setHaystackBaleRetryUntil(int $retryUntil): static;
}
