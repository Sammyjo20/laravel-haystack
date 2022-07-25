<?php

namespace Sammyjo20\LaravelJobStack;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;
use Sammyjo20\LaravelJobStack\Actions\ProcessCompletedJob;
use Sammyjo20\LaravelJobStack\Helpers\Stackable;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelJobStackServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-job-stack')
            ->hasConfigFile()
            ->hasMigrations([
                'create_job_stacks_table',
                'create_job_stack_rows_table',
            ]);
    }

    /**
     * @return void
     */
    public function bootingPackage()
    {
        if (config('job-stack.process_automatically', false) === true) {
            $this->listenToJobs();
        }
    }

    /**
     * Listen to jobs.
     *
     * @return void
     */
    private function listenToJobs(): void
    {
        // We'll firstly append the job_stack_id onto the queued job's
        // payload. This will be resolved in our process completed
        // job logic.

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            $jobData = $payload['data'];
            $command = $payload['data']['command'] ?? null;

            if ($command instanceof ShouldQueue && Stackable::isStackable($command) === true) {
                $jobData = array_merge($payload['data'], array_filter([
                    'job_stack_id' => $command->getJobStack()->getKey(),
                ]));
            }

            return ['data' => $jobData];
        });

        // After every processed job, we will execute this, which will determine if it should
        // run the next job in the chain.

        Queue::after(fn (JobProcessed $event) => (new ProcessCompletedJob($event))->execute());
    }
}
