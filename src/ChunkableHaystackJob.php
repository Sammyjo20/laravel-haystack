<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack;

use Sammyjo20\ChunkableJobs\ChunkableJob;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

abstract class ChunkableHaystackJob extends ChunkableJob implements StackableJob
{
    use Stackable;

    /**
     * Extra properties to unset
     *
     * @var array|string[]
     */
    protected array $ignoredProperties = [
        'haystack', 'haystackBaleId', 'haystackBaleAttempts',
    ];

    /**
     * Dispatch the next chunk
     */
    protected function dispatchNextChunk(object $job): void
    {
        if (is_null($this->haystack)) {
            parent::dispatchNextChunk($job);

            return;
        }

        $this->prependToHaystack($job, $this->chunkInterval);
    }
}
