<?php

namespace Sammyjo20\LaravelJobStack;

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
}
