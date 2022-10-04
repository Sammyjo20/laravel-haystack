<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\FailJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\CacheJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\ExcitedJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\SetDataJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoCacheJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\CustomOptionJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AppendingDelayJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\GetAndCacheDataJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\GetAllAndCacheDataJob;

test('a stackable job can call the next job', function () {
    $haystack = Haystack::build()
        ->addJob(new CacheJob('name', 'Sam'))
        ->addJob(new CacheJob('legend', 'Gareth'))
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
        ->addJob(new CacheJob('legend', 'Gareth'))
        ->dispatch();

    expect(cache()->get('name'))->toEqual('Sam');
    expect(cache()->get('legend'))->toBeNull();
});

test('a stackable job can fail a haystack early', function () {
    Haystack::build()
        ->addJob(new CacheJob('name', 'Sam'))
        ->addJob(new FailJob())
        ->addJob(new CacheJob('legend', 'Gareth'))
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
    Bus::fake([
        CacheJob::class,
    ]);

    Haystack::build()
        ->addJob(new AppendingDelayJob)
        ->dispatch();

    Bus::assertDispatched(CacheJob::class, function (CacheJob $job) {
        return $job->delay === 120 && $job->queue === 'cowboy' && $job->connection === 'redis';
    });
});

test('a stackable job can set data and another job can retrieve it', function () {
    expect(cache()->get('name'))->toBeNull();

    Haystack::build()
        ->addJob(new SetDataJob('name', 'Sam'))
        ->addJob(new GetAndCacheDataJob('name'))
        ->dispatch();

    expect(cache()->get('name'))->toEqual('Sam');
});

test('a stackable job can add haystack data', function () {
    Haystack::build()
        ->addJob(new SetDataJob('name', 'Sam'))
        ->then(function ($data) {
            cache()->set('data', $data);
        })
        ->dispatch();

    expect(cache()->get('data'))->toEqual(new Collection(['name' => 'Sam']));
});

test('a stackable job can get all haystack data', function () {
    Haystack::build()
        ->addJob(new SetDataJob('name', 'Sam'))
        ->addJob(new SetDataJob('boss', 'Gareth'))
        ->addJob(new GetAllAndCacheDataJob)
        ->dispatch();

    expect(cache()->get('all'))->toEqual(new Collection([
        'name' => 'Sam',
        'boss' => 'Gareth',
    ]));
});

test('a stackable job can be dispatched without being on a haystack', function () {
    AutoCacheJob::dispatch('name', 'Sammy');

    expect(cache()->get('name'))->toEqual('Sammy');
});

test('you can get a haystack option from the stackable job', function () {
    $haystack = Haystack::build()
        ->addJob(new CustomOptionJob('yeeHaw'))
        ->setOption('yeeHaw', '🤠')
        ->dispatch();

    expect(cache()->get('option'))->toEqual('🤠');
    expect(cache()->get('allOptions'))->toEqual($haystack->options);
});
