<?php

declare(strict_types=1);

use Sammyjo20\LaravelHaystack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sammyjo20\LaravelHaystack\LaravelHaystackServiceProvider;

uses(TestCase::class)->in(__DIR__);
uses(RefreshDatabase::class)->in(__DIR__);

function withAutomaticProcessing(): void
{
    config()->set('haystack.process_automatically', true);

    // It's a bit hacky, but we'll run the "bootingPackage" method
    // on the provider to start recording events.

    (new LaravelHaystackServiceProvider(app()))->bootingPackage();
}

function dontDeleteHaystack(): void
{
    config()->set('haystack.delete_finished_haystacks', false);
}

function withJobsTable(): void
{
    $migration = include __DIR__.'/Migrations/create_jobs_table.php';
    $migration->up();

    $migration = include __DIR__.'/Migrations/create_failed_jobs_table.php';
    $migration->up();
}
