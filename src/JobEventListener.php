<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack;

use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class JobEventListener
{
    /**
     * Static make helper to create class.
     */
    public static function make(): static
    {
        return new static();
    }

    /**
     * Create the job payload.
     */
    public function createPayloadUsing($connection, $queue, $payload): array
    {
        $command = $payload['data']['command'] ?? null;
        $data = $payload['data'];

        // When we find a job that is not a StackableJob, and therefor not a job
        // inside a Haystack, we will return early.

        if (! $command instanceof StackableJob) {
            return ['data' => $data];
        }

        // If we are about to process a StackableJob, we should add the "haystack_id"
        // to the job payload. This will help us resolve the haystack when the job
        // is processed by the "handleJobProcessed" event.

        $data = array_merge($payload['data'], [
            'haystack_id' => $command->getHaystack()->getKey(),
        ]);

        return ['data' => $data];
    }

    /**
     * Handle the "JobProcessed" event.
     */
    public function handleJobProcessed(JobProcessed $event): void
    {
        $processedJob = $event->job;
        $payload = $processedJob->payload();

        // We'll firstly attempt to get the haystack from the payload. The reason
        // we do this first, is because if we attempt to unserialize the job
        // first and the haystack is deleted, it will throw an exception
        // because the model now longer exists.

        $haystack = $this->getHaystackFromPayload($payload);

        if (! $haystack instanceof Haystack) {
            return;
        }

        // We will next attempt to decode the "command" from the job payload.
        // The command data contains a serialized version of the job.

        $job = $this->unserializeJobFromPayload($payload);

        if (! $job instanceof StackableJob) {
            return;
        }

        // Once we have unserialized the job, and we know it is a stackable job
        // we should check if the job has been released. If the job has been
        // released we will wait for the job to be processed again. If the
        // processed job is a "SyncJob" we should ignore the check since
        // sync jobs will be processed straight away.

        if ($processedJob instanceof SyncJob === false && $processedJob->isReleased() === true) {
            return;
        }

        // Once we have found the Haystack, we'll check if the current job has
        // failed. If it has, then we'll just stop here. If it has failed
        // the fail handler will continue for us.

        if ($processedJob->hasFailed()) {
            return;
        }

        // Dispatch the next job...

        $haystack->dispatchNextJob($job);
    }

    /**
     * Handle the "JobFailed" event.
     */
    public function handleFailedJob(JobFailed $event): void
    {
        $processedJob = $event->job;
        $payload = $processedJob->payload();

        // We'll firstly attempt to get the haystack from the payload. The reason
        // we do this first, is because if we attempt to unserialize the job
        // first and the haystack is deleted, it will throw an exception
        // because the model now longer exists.

        $haystack = $this->getHaystackFromPayload($payload);

        if (! $haystack instanceof Haystack) {
            return;
        }

        // We will next attempt to decode the "command" from the job payload.
        // The command data contains a serialized version of the job.

        $job = $this->unserializeJobFromPayload($payload);

        if (! $job instanceof StackableJob) {
            return;
        }

        // If allow failures is turned on, we'll dispatch the next job.

        if ($haystack->options->allowFailures === true) {
            $haystack->dispatchNextJob($job);

            return;
        }

        // Otherwise we'll fail the Haystack

        $haystack->fail();
    }

    /**
     * Unserialize the job from the job payload.
     */
    private function unserializeJobFromPayload(array $payload): ?object
    {
        if (! isset($payload['data']['command'])) {
            return null;
        }

        return unserialize($payload['data']['command'], ['allowed_classes' => true]);
    }

    /**
     * Attempt to find the haystack model from the job payload.
     */
    private function getHaystackFromPayload(array $payload): ?Haystack
    {
        $haystackId = $payload['data']['haystack_id'] ?? null;

        if (blank($haystackId)) {
            return null;
        }

        return Haystack::find($haystackId);
    }
}
