<?php

namespace Sammyjo20\LaravelJobStack\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Sammyjo20\LaravelJobStack\LaravelJobStackServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Sammyjo20\\LaravelJobStack\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelJobStackServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $migration = include __DIR__.'/../database/migrations/create_job_stacks_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_job_stack_rows_table.php.stub';
        $migration->up();
    }
}
