<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack;

use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Sammyjo20\LaravelHaystack\Console\Commands\HaystacksClear;
use Sammyjo20\LaravelHaystack\Console\Commands\HaystacksForget;
use Sammyjo20\LaravelHaystack\Console\Commands\HaystacksResume;

class LaravelHaystackServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/haystack.php',
            'haystack'
        );
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishConfigAndMigrations()
            ->registerCommands()
            ->registerQueueListeners();
    }

    /**
     * Public the config file and migrations
     *
     * @return $this
     */
    protected function publishConfigAndMigrations(): static
    {
        $this->publishes([
            __DIR__.'/../config/haystack.php' => config_path('haystack.php'),
        ], 'haystack-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'haystack-migrations');

        return $this;
    }

    /**
     * Register commands
     *
     * @return $this
     */
    protected function registerCommands(): static
    {
        if (! $this->app->runningInConsole()) {
            return $this;
        }

        $this->commands([
            HaystacksClear::class,
            HaystacksForget::class,
            HaystacksResume::class,
        ]);

        return $this;
    }

    /**
     * Listen to the queue events.
     *
     * @return $this
     */
    public function registerQueueListeners(): static
    {
        if (config('haystack.process_automatically') !== true) {
            return $this;
        }

        Queue::createPayloadUsing(fn ($connection, $queue, $payload) => JobEventListener::make()->createPayloadUsing($connection, $queue, $payload));

        Queue::after(fn (JobProcessed $event) => JobEventListener::make()->handleJobProcessed($event));

        Queue::failing(fn (JobFailed $event) => JobEventListener::make()->handleFailedJob($event));

        return $this;
    }
}
