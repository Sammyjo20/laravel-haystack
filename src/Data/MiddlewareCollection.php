<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Data;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;
use Sammyjo20\LaravelHaystack\Helpers\ClosureHelper;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;

class MiddlewareCollection
{
    /**
     * The Middleware
     *
     * @var array
     */
    public array $data = [];

    /**
     * Add the middleware to the collection
     *
     * @param  Closure|array|callable  $value
     * @return $this
     *
     * @throws PhpVersionNotSupportedException
     */
    public function add(Closure|array|callable $value): static
    {
        if (is_array($value)) {
            $value = static fn () => $value;
        }

        $this->data[] = new SerializableClosure(ClosureHelper::fromCallable($value));

        return $this;
    }

    /**
     * Call the whole middleware stack and convert it into an array
     *
     * @return array
     */
    public function toMiddlewareArray(): array
    {
        return collect($this->data)
            ->map(function (SerializableClosure $closure) {
                $result = $closure();

                return is_array($result) ? $result : [$result];
            })
            ->flatten()
            ->filter(fn ($value) => is_object($value))
            ->toArray();
    }

    /**
     * Check if the middleware is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * Check if the middleware is not empty
     *
     * @return bool
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
