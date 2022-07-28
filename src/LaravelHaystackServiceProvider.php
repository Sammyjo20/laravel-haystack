<?php

namespace Sammyjo20\LaravelHaystack;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;
use Sammyjo20\LaravelHaystack\Actions\ProcessCompletedJob;
use Sammyjo20\LaravelHaystack\Helpers\Stackable;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelHaystackServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-haystack')
            ->hasConfigFile()
            ->hasMigrations([
                'create_haystacks_table',
                'create_haystack_bales_table',
            ]);
    }

    /**
     * @return void
     */
    public function bootingPackage()
    {
        if (config('haystack.process_automatically', false) === true) {
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
        // We'll firstly append the haystack_id onto the queued job's
        // payload. This will be resolved in our process completed
        // job logic.

        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            $jobData = $payload['data'];
            $command = $payload['data']['command'] ?? null;

            if ($command instanceof ShouldQueue && Stackable::isStackable($command) === true) {
                $jobData = array_merge($payload['data'], array_filter([
                    'haystack_id' => $command->getHaystack()->getKey(),
                ]));
            }

            return ['data' => $jobData];
        });

        // After every processed job, we will execute this, which will determine if it should
        // run the next job in the chain.

        Queue::after(fn(JobProcessed $event) => (new ProcessCompletedJob($event))->execute());
    }
}
