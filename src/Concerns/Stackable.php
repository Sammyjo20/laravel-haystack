<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Enums\FinishStatus;
use Sammyjo20\LaravelHaystack\Models\HaystackBale;
use Sammyjo20\LaravelHaystack\Data\HaystackOptions;
use Sammyjo20\LaravelHaystack\Helpers\CarbonHelper;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Tests\Exceptions\StackableException;

trait Stackable
{
    /**
     * The Haystack the job has.
     */
    protected ?Haystack $haystack = null;

    /**
     * The ID of the haystack "bale". Used for deleting.
     */
    protected int $haystackBaleId;

    /**
     * The attempts on haystack "bale".
     */
    protected int $haystackBaleAttempts;

    /**
     * Get the job stack.
     */
    public function getHaystack(): Haystack
    {
        return $this->haystack;
    }

    /**
     * Set the Haystack onto the job.
     *
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
     * @return $this
     */
    public function prependToHaystack(StackableJob|Collection|array $jobs, int $delayInSeconds = 0, string $queue = null, string $connection = null): static
    {
        $this->haystack->addJobs($jobs, $delayInSeconds, $queue, $connection, true);

        return $this;
    }

    /**
     * Get the haystack bale id
     */
    public function getHaystackBaleId(): int
    {
        return $this->haystackBaleId;
    }

    /**
     * Set the Haystack bale ID.
     *
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
     * @return $this
     */
    public function setHaystackData(string $key, mixed $value, string $cast = null): static
    {
        $this->haystack->setData($key, $value, $cast);

        return $this;
    }

    /**
     * Get data on the haystack.
     */
    public function getHaystackData(string $key, mixed $default = null): mixed
    {
        return $this->haystack->getData($key, $default);
    }

    /**
     * Get a shared model
     */
    public function getHaystackModel(string $model, mixed $default = null): ?Model
    {
        return $this->haystack->getModel($model, $default);
    }

    /**
     * Set a shared model
     *
     * @return $this
     *
     * @throws \Sammyjo20\LaravelHaystack\Exceptions\HaystackModelExists
     */
    public function setHaystackModel(Model $model, string $key): static
    {
        $this->haystack->setModel($model, $key);

        return $this;
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
     */
    public function getHaystackBaleAttempts(): int
    {
        return $this->haystackBaleAttempts;
    }

    /**
     * Set the haystack bale attempts.
     *
     * @return $this
     */
    public function setHaystackBaleAttempts(int $attempts): static
    {
        $this->haystackBaleAttempts = $attempts;

        return $this;
    }

    /**
     * Get the options on the Haystack
     */
    public function getHaystackOptions(): HaystackOptions
    {
        return $this->haystack->options;
    }

    /**
     * Retrieve a haystack option
     */
    public function getHaystackOption(string $option, mixed $default = null): mixed
    {
        return $this->haystack->options->$option ?? $default;
    }
}
