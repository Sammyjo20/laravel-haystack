<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AppendingDelayJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\CacheJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\ExcitedJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\FailJob;

test('a stackable job can call the next job', function () {
    $haystack = Haystack::build()
        ->addJob(new CacheJob('name', 'Sam'))
        ->addJob(new CacheJob('legend','Gareth'))
        ->create();

    expect($haystack)->toBeInstanceOf(Haystack::class);

    $haystack->start();

    expect(cache()->get('name'))->toEqual('Sam');
    expect(cache()->get('legend'))->toEqual('Gareth');

    $this->expectException(ModelNotFoundException::class);

    $haystack->refresh();
});

test('a stackable job can finish a haystack early', function () {
    Haystack::build()
        ->addJob(new CacheJob('name', 'Sam'))
        ->addJob(new ExcitedJob())
        ->addJob(new CacheJob('legend','Gareth'))
        ->dispatch();

    expect(cache()->get('name'))->toEqual('Sam');
    expect(cache()->get('legend'))->toBeNull();
});

test('a stackable job can fail a haystack early', function () {
    Haystack::build()
        ->addJob(new CacheJob('name', 'Sam'))
        ->addJob(new FailJob())
        ->addJob(new CacheJob('legend','Gareth'))
        ->dispatch();

    expect(cache()->get('name'))->toEqual('Sam');
    expect(cache()->get('legend'))->toBeNull();
});

test('when a stackable job is created the haystack is loaded', function () {
    Queue::fake();

    $haystack = Haystack::build()
        ->addJob(new CacheJob('name', 'Sam'))
        ->dispatch();

    Queue::assertPushed(CacheJob::class, function (CacheJob $job) use ($haystack) {
        return $job->getHaystack() === $haystack;
    });
});

test('you can dispatch the next job in the haystack with a custom delay', function () {
    Queue::fake([
        CacheJob::class,
    ]);

    Haystack::build()
        ->addJob(new AppendingDelayJob)
        ->dispatch();

    Queue::assertPushed(CacheJob::class, function (CacheJob $job) {
        return $job->delay === 120 && $job->queue === 'cowboy' && $job->connection === 'redis';
    });
});
