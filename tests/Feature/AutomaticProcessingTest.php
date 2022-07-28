<?php

use Illuminate\Support\Facades\Config;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\Middleware;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;

test('it can process jobs automatically', function () {

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
