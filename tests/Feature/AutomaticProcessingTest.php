<?php

use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\Middleware;

test('it can process jobs automatically', function () {
    config()->set('queue.default', 'database');
    config()->set('queue.connections.database.retry_after', 5);

    $haystack = Haystack::build()
        ->addBale(new NameJob('Sam'))
        ->addBale(new NameJob('Charlotte'))
        ->addJob(new NameJob('Gareth'))
        ->withMiddleware([
            new Middleware(),
        ])
        ->then(function () {
            ray('Finished!')->green();
        })
        ->catch(function () {
            ray('Failed!')->red();
        })
        ->finally(function () {
            ray('I always happen');
        })
        ->dispatch();

    $this->artisan('queue:work');
})->skip('Try getting this to work');

test('it throws an exception if you try to queue the next job with automatic queuing turned on', function () {
})->skip();
