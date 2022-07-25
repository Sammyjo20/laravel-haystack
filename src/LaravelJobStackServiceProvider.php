<?php

namespace Sammyjo20\LaravelJobStack;

use Sammyjo20\LaravelJobStack\Commands\LaravelJobStackCommand;
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
            ->hasViews()
            ->hasMigration('create_laravel-job-stack_table')
            ->hasCommand(LaravelJobStackCommand::class);
    }
}
