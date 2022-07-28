<?php

namespace Sammyjo20\LaravelHaystack\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Sammyjo20\LaravelHaystack\LaravelHaystackServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'Sammyjo20\\LaravelHaystack\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelHaystackServiceProvider::class,
        ];
    }

    protected function getApplicationTimezone($app)
    {
        return 'Europe/London';
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing.foreign_key_constraints', true);
        config()->set('queue.default', 'database');
        config()->set('queue.connections.database.retry_after', 5);

        $migration = include __DIR__ . '/../database/migrations/create_haystacks_table.php.stub';
        $migration->up();

        $migration = include __DIR__ . '/../database/migrations/create_haystack_bales_table.php.stub';
        $migration->up();

        $migration = include __DIR__ . '/Database/Migrations/jobs.php';
        $migration->up();
    }
}
