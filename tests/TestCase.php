<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Tests;

use Psr\Container\NotFoundExceptionInterface;
use Orchestra\Testbench\TestCase as Orchestra;
use Psr\Container\ContainerExceptionInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Sammyjo20\LaravelHaystack\HaystackServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Sammyjo20\\LaravelHaystack\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            HaystackServiceProvider::class,
        ];
    }

    protected function getApplicationTimezone($app): string
    {
        return 'Europe/London';
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', config()->get('haystack.db_connection'));
        config()->set('database.connections.testing.foreign_key_constraints', true);
        config()->set('haystack.process_automatically', false);

        $migration = include __DIR__.'/../database/migrations/create_haystacks_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_haystack_bales_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_haystack_data_table.php.stub';
        $migration->up();
    }
}
