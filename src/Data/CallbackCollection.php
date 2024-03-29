<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Data;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;
use Sammyjo20\LaravelHaystack\Helpers\ClosureHelper;

class CallbackCollection
{
    /**
     * Callbacks for the "then" event
     */
    public array $onThen = [];

    /**
     * Callbacks for the "catch" event
     */
    public array $onCatch = [];

    /**
     * Callbacks for the "finally" event
     */
    public array $onFinally = [];

    /**
     * Callbacks for the "paused" event
     */
    public array $onPaused = [];

    /**
     * Add a "then" callback
     *
     * @return $this
     *
     * @throws \Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException
     */
    public function addThen(Closure|callable $closure): static
    {
        return $this->addCallback('onThen', $closure);
    }

    /**
     * Add a "catch" callback
     *
     * @return $this
     *
     * @throws \Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException
     */
    public function addCatch(Closure|callable $closure): static
    {
        return $this->addCallback('onCatch', $closure);
    }

    /**
     * Add a "finally" callback
     *
     * @return $this
     *
     * @throws \Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException
     */
    public function addFinally(Closure|callable $closure): static
    {
        return $this->addCallback('onFinally', $closure);
    }

    /**
     * Add a "paused" callback
     *
     * @return $this
     *
     * @throws \Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException
     */
    public function addPaused(Closure|callable $closure): static
    {
        return $this->addCallback('onPaused', $closure);
    }

    /**
     * Add a callback to a given property
     *
     * @return $this
     *
     * @throws \Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException
     */
    protected function addCallback(string $property, Closure|callable $closure): static
    {
        $this->$property[] = new SerializableClosure(ClosureHelper::fromCallable($closure));

        return $this;
    }

    /**
     * Check if the callbacks are empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->onThen) && empty($this->onCatch) && empty($this->onFinally) && empty($this->onPaused);
    }

    /**
     * Check if the callbacks are not empty
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Convert the object ready to be serialized
     *
     * @return $this|null
     */
    public function toSerializable(): ?static
    {
        return $this->isNotEmpty() ? $this : null;
    }
}
