<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Enums\FinishStatus;
use Sammyjo20\LaravelHaystack\Models\HaystackBale;
use Sammyjo20\LaravelHaystack\Helpers\CarbonHelper;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
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
     * The attempts on haystack "bale".
     *
     * @var int
     */
    protected int $haystackBaleAttempts;

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
     *
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
     * @param  int|CarbonInterface|null  $delayInSecondsOrCarbon
     * @return $this
     *
     * @throws StackableException
     */
    public function nextBale(int|CarbonInterface $delayInSecondsOrCarbon = null): static
    {
        return $this->nextJob($delayInSecondsOrCarbon);
    }

    /**
     * Release the job for haystack to process later.
     *
     * @param  int|CarbonInterface  $delayInSecondsOrCarbon
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
        $this->haystack->finish(FinishStatus::Failure);

        return $this;
    }

    /**
     * Append jobs to the haystack.
     *
     * @param  StackableJob|Collection|array  $jobs
     * @param  int  $delayInSeconds
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return $this
     */
    public function appendToHaystack(StackableJob|Collection|array $jobs, int $delayInSeconds = 0, string $queue = null, string $connection = null): static
    {
        $this->haystack->addJobs($jobs, $delayInSeconds, $queue, $connection, false);

        return $this;
    }

    /**
     * Prepend jobs to the haystack.
     *
     * @param  StackableJob|Collection|array  $jobs
     * @param  int  $delayInSeconds
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return $this
     */
    public function prependToHaystack(StackableJob|Collection|array $jobs, int $delayInSeconds = 0, string $queue = null, string $connection = null): static
    {
        $this->haystack->addJobs($jobs, $delayInSeconds, $queue, $connection, true);

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
     * @param  int  $haystackBaleId
     * @return $this
     */
    public function setHaystackBaleId(int $haystackBaleId): static
    {
        $this->haystackBaleId = $haystackBaleId;

        return $this;
    }

    /**
     * Pause the haystack. We also need to delete the current row.
     *
     * @param  int|CarbonInterface  $delayInSecondsOrCarbon
     * @return $this
     *
     * @throws StackableException
     */
    public function pauseHaystack(int|CarbonInterface $delayInSecondsOrCarbon): static
    {
        if (config('haystack.process_automatically', false) === false) {
            throw new StackableException('The "pauseHaystack" method is unavailable when "haystack.process_automatically" is disabled. Use the "nextJob" with a delay provided instead.');
        }

        $resumeAt = CarbonHelper::createFromSecondsOrCarbon($delayInSecondsOrCarbon);

        $this->haystack->pause($resumeAt);

        // We need to make sure that we delete the current haystack bale to stop it
        // from being processed when the haystack is resumed.

        HaystackBale::query()->whereKey($this->getHaystackBaleId())->delete();

        return $this;
    }

    /**
     * Set data on the haystack.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  string|null  $cast
     * @return $this
     */
    public function setHaystackData(string $key, mixed $value, string $cast = null): static
    {
        $this->haystack->setData($key, $value, $cast);

        return $this;
    }

    /**
     * Get data on the haystack.
     *
     * @param  string  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public function getHaystackData(string $key, mixed $default = null): mixed
    {
        return $this->haystack->getData($key, $default);
    }

    /**
     * Get all data on the haystack.
     *
     * @return mixed
     */
    public function allHaystackData(): Collection
    {
        return $this->haystack->allData();
    }

    /**
     * Get the haystack bale attempts.
     *
     * @return int
     */
    public function getHaystackBaleAttempts(): int
    {
        return $this->haystackBaleAttempts;
    }

    /**
     * Set the haystack bale attempts.
     *
     * @param  int  $attempts
     * @return $this
     */
    public function setHaystackBaleAttempts(int $attempts): static
    {
        $this->haystackBaleAttempts = $attempts;

        return $this;
    }
}
