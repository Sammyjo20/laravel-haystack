<?php

use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Builders\HaystackBuilder;
use Sammyjo20\LaravelHaystack\Data\PendingHaystackBale;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\Middleware;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\InvokableClass;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NotStackableJob;

test('you can add jobs to the haystack builder', function () {
    $builder = new HaystackBuilder;

    expect($builder->getJobs())->toHaveCount(0);

    $samJob = new NameJob('Sam');
    $garethJob = new NameJob('Gareth');

    $builder->addJob($samJob);
    $builder->addBale($garethJob);

    $jobs = $builder->getJobs();

    expect($jobs)->toHaveCount(2);
    expect($jobs[0])->toBeInstanceOf(PendingHaystackBale::class);
    expect($jobs[1])->toBeInstanceOf(PendingHaystackBale::class);

    $samPendingJob = $jobs[0];
    $garethPendingJob = $jobs[1];

    expect($samPendingJob->job)->toEqual($samJob);
    expect($samPendingJob->delayInSeconds)->toEqual(0);
    expect($samPendingJob->queue)->toBeNull();
    expect($samPendingJob->connection)->toBeNull();

    expect($garethPendingJob->job)->toEqual($garethJob);
    expect($garethPendingJob->delayInSeconds)->toEqual(0);
    expect($garethPendingJob->queue)->toBeNull();
    expect($garethPendingJob->connection)->toBeNull();
});

test('you can specify a global timeout, queue and connection on the builder for all jobs', function () {
    $builder = new HaystackBuilder;

    $builder->withDelay(60);
    $builder->onConnection('database');
    $builder->onQueue('testing');

    expect($builder->getGlobalDelayInSeconds())->toEqual(60);
    expect($builder->getGlobalConnection())->toEqual('database');
    expect($builder->getGlobalQueue())->toEqual('testing');
});

test('you can specify a closure or a callable to happen at the end of a successful haystack', function () {
    $builder = new HaystackBuilder;

    $builder->then(fn () => 'Hello');

    expect($builder->getOnThen())->toEqual(fn () => 'Hello');

    $builder->then(new InvokableClass);

    expect($builder->getOnThen())->toEqual(fn () => new InvokableClass);
});

test('you can specify a closure to happen at the end any haystack', function () {
    $builder = new HaystackBuilder;

    $builder->finally(fn () => 'Hello');

    expect($builder->getOnFinally())->toEqual(fn () => 'Hello');

    $builder->finally(new InvokableClass);

    expect($builder->getOnFinally())->toEqual(fn () => new InvokableClass);
});

test('you can specify a closure to happen on an erroneous haystack', function () {
    $builder = new HaystackBuilder;

    $builder->catch(fn () => 'Hello');

    expect($builder->getOnCatch())->toEqual(fn () => 'Hello');

    $builder->catch(new InvokableClass);

    expect($builder->getOnCatch())->toEqual(fn () => new InvokableClass);
});

test('you can specify middleware as a closure, invokable class or an array', function () {
    $builder = new HaystackBuilder;

    $builder->withMiddleware(fn () => [new Middleware()]);

    expect($builder->getGlobalMiddleware())->toEqual(fn () => [new Middleware()]);

    $builder->withMiddleware(new InvokableClass);

    expect($builder->getGlobalMiddleware())->toEqual(fn () => new InvokableClass);

    $builder->withMiddleware([new Middleware]);

    expect($builder->getGlobalMiddleware())->toEqual(fn () => [new Middleware()]);
});

test('you can create a haystack from a builder', function () {
    $builder = new HaystackBuilder;

    $haystack = $builder->create();

    expect($haystack)->toBeInstanceOf(Haystack::class);
});

test('you can specify a custom delay, connection or queue on a per job basis', function () {
    $builder = new HaystackBuilder;

    $builder->addJob(new NameJob('Sam'), 60, 'testing', 'database');

    $jobs = $builder->getJobs();

    expect($jobs[0]->delayInSeconds)->toEqual(60);
    expect($jobs[0]->queue)->toEqual('testing');
    expect($jobs[0]->connection)->toEqual('database');
});

test('you can specify a custom delay, connection or queue on a per job basis which takes priority over globals', function () {
    $builder = new HaystackBuilder;

    $builder->withDelay(120);
    $builder->onQueue('cowboy');
    $builder->onConnection('redis');

    $builder->addJob(new NameJob('Sam'), 60, 'testing', 'database');

    $jobs = $builder->getJobs();

    expect($jobs[0]->delayInSeconds)->toEqual(60);
    expect($jobs[0]->queue)->toEqual('testing');
    expect($jobs[0]->connection)->toEqual('database');
});

test('you can dispatch a haystack right away', function () {
    $builder = new HaystackBuilder;

    $haystack = $builder->dispatch();

    expect($haystack->started)->toBeTrue();
});

test('it throws an exception if you try to add a job without the stackable class', function () {
    $builder = new HaystackBuilder;

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('The provided job does not contain the "Stackable" trait.');

    $builder->addJob(new NotStackableJob);
});
