<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack;

use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobFailed;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Queue\Events\JobProcessed;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Sammyjo20\LaravelHaystack\Console\Commands\HaystacksClear;
use Sammyjo20\LaravelHaystack\Console\Commands\HaystacksForget;
use Sammyjo20\LaravelHaystack\Console\Commands\ResumeHaystacks;

class LaravelHaystackServiceProvider extends PackageServiceProvider
{
    /**
     * Welcome to Laravel Haystack, world!
     *
     * @param  Package  $package
     * @return void
     */
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
                'create_haystack_data_table',
            ])
            ->hasCommand(ResumeHaystacks::class)
            ->hasCommand(HaystacksForget::class)
            ->hasCommand(HaystacksClear::class);
    }

    /**
     * Hook when the package is booting.
     *
     * @return void
     */
    public function bootingPackage(): void
    {
        $this->listenToQueueEvents();
    }

    /**
     * Listen to the queue events.
     *
     * @return void
     */
    public function listenToQueueEvents(): void
    {
        if (config('haystack.process_automatically') !== true) {
            return;
        }

        Queue::createPayloadUsing(fn ($connection, $queue, $payload) => JobEventListener::make()->createPayloadUsing($connection, $queue, $payload));

        Queue::after(fn (JobProcessed $event) => JobEventListener::make()->handleJobProcessed($event));

        Queue::failing(fn (JobFailed $event) => JobEventListener::make()->handleFailedJob($event));
    }
}
