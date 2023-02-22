<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setupForMySqlTest()
    {
        config()->set('haystack.db_connection', 'mysql');
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.port', '33066');
        config()->set('database.connections.mysql.database', 'testing');

        Schema::dropAllTables();

        $migration = include __DIR__.'/../database/migrations/create_haystacks_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_haystack_bales_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_haystack_data_table.php.stub';
        $migration->up();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setupForPgSqlTest()
    {
        config()->set('haystack.db_connection', 'pgsql');
        config()->set('database.default', 'pgsql');
        config()->set('database.connections.pgsql.port', '54321');
        config()->set('database.connections.pgsql.username', 'postgres');
        config()->set('database.connections.pgsql.schema', 'public');
        config()->set('database.connections.pgsql.database', 'testing');

        Schema::setConnection(DB::connection('pgsql'));
        Schema::dropAllTables();

        $migration1 = include __DIR__.'/../database/migrations/create_haystacks_table.php.stub';
        $migration1->up();

        $migration2 = include __DIR__.'/../database/migrations/create_haystack_bales_table.php.stub';
        $migration2->up();

        $migration3 = include __DIR__.'/../database/migrations/create_haystack_data_table.php.stub';
        $migration3->up();
    }
}
