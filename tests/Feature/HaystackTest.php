<?php

use Illuminate\Support\Facades\Queue;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\OrderCheckCacheJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AppendingOrderCheckCacheJob;

test('you can start a haystack', function () {
    Queue::fake();

    $haystack = Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->addJob(new NameJob('Steve'))
        ->create();

    Queue::assertNothingPushed();

    $haystack->start();

    expect($haystack->started)->toBeTrue();

    Queue::assertPushed(NameJob::class, function ($job) {
        return $job->name === 'Sam';
    });
});

test('you can finish a haystack early', function () {
    Queue::fake();

    $variable = 0;

    $haystack = Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->then(function () use (&$variable) {
            $variable++;
        })
        ->create();

    expect($variable)->toEqual(0);

    $haystack->finish();

    expect($variable)->toEqual(1);

    Queue::assertNothingPushed();
});

test('you can fail a haystack', function () {
    Queue::fake();

    $variable = 0;

    $haystack = Haystack::build()
        ->addJob(new NameJob('Sam'))
        ->catch(function () use (&$variable) {
            $variable++;
        })
        ->create();

    expect($variable)->toEqual(0);

    $haystack->fail();

    expect($variable)->toEqual(1);

    Queue::assertNothingPushed();
});

test('jobs are processed in the right orrder', function () {
    Haystack::build()
        ->addJob(new OrderCheckCacheJob('Sam'))
        ->addJob(new OrderCheckCacheJob('Steve'))
        ->addJob(new OrderCheckCacheJob('Taylor'))
        ->dispatch();

    expect(cache()->get('order'))->toEqual(['Sam', 'Steve', 'Taylor']);
});

test('you can append a job onto the haystack in a job and it is run at the end', function () {
    Haystack::build()
        ->addJob(new AppendingOrderCheckCacheJob('Sam'))
        ->addJob(new OrderCheckCacheJob('Taylor'))
        ->dispatch();

    expect(cache()->get('order'))->toEqual(['Sam', 'Taylor', 'Sam']);
});
