<?php

namespace Sammyjo20\LaravelJobStack;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Config;
use Sammyjo20\LaravelJobStack\Actions\ProcessCompletedJob;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Illuminate\Support\Facades\Queue;

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
            Queue::after(function (JobProcessed $event) {
                (new ProcessCompletedJob($event))->execute();
            });
        }
    }
}
