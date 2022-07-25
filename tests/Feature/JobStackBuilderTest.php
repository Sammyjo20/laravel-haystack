<?php

use Sammyjo20\LaravelJobStack\Models\JobStack;
use Sammyjo20\LaravelJobStack\Tests\Fixtures\Jobs\ExampleJob;

it('works', function () {
    $jobStack = JobStack::build()
        ->addJob(new ExampleJob('Sam'))
        ->addJob(new ExampleJob('Andy'))
        ->then(function () {
            ray('I have finished successfully')->green();
        })
        ->catch(function () {
            ray('I have failed')->red();
        })
        ->finally(function () {
            ray('This always happens.')->purple();
        })
        ->dispatch();

    dd($jobStack);
});
