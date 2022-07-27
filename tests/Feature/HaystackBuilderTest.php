<?php

use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\Middleware;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;

it('works', function () {
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

    dd($haystack);
});

test('you can provide a closure, invokable class or array to the middleware', function () {

});
