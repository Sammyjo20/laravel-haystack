<?php

declare(strict_types=1);

use Sammyjo20\LaravelHaystack\Models\Haystack;
use Laravel\SerializableClosure\SerializableClosure;
use Sammyjo20\LaravelHaystack\Builders\HaystackBuilder;
use Sammyjo20\LaravelHaystack\Data\PendingHaystackBale;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\Middleware;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\InvokableClass;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables\InvokableMiddleware;

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

test('you can specify a closure or a callable to happen at the end of a successful haystack and it will chain functions', function () {
    $builder = new HaystackBuilder;

    $builder->then(fn () => 'Hello');

    expect($builder->getCallbacks()->onThen)->toEqual([
        new SerializableClosure(fn () => 'Hello'),
    ]);

    $builder->then(new InvokableClass);

    expect($builder->getCallbacks()->onThen)->toEqual([
        new SerializableClosure(fn () => 'Hello'),
        new SerializableClosure(fn () => new InvokableClass),
    ]);
});

test('you can specify a closure to happen at the end of any haystack', function () {
    $builder = new HaystackBuilder;

    $builder->finally(fn () => 'Hello');

    expect($builder->getCallbacks()->onFinally)->toEqual([
        new SerializableClosure(fn () => 'Hello'),
    ]);

    $builder->finally(new InvokableClass);

    expect($builder->getCallbacks()->onFinally)->toEqual([
        new SerializableClosure(fn () => 'Hello'),
        new SerializableClosure(fn () => new InvokableClass),
    ]);
});

test('you can specify a closure to happen on an erroneous haystack', function () {
    $builder = new HaystackBuilder;

    $builder->catch(fn () => 'Hello');

    expect($builder->getCallbacks()->onCatch)->toEqual([
        new SerializableClosure(fn () => 'Hello'),
    ]);

    $builder->catch(new InvokableClass);

    expect($builder->getCallbacks()->onCatch)->toEqual([
        new SerializableClosure(fn () => 'Hello'),
        new SerializableClosure(fn () => new InvokableClass),
    ]);
});

test('you can specify a closure to happen on a paused haystack', function () {
    $builder = new HaystackBuilder;

    $builder->paused(fn () => 'Hello');

    expect($builder->getCallbacks()->onPaused)->toEqual([
        new SerializableClosure(fn () => 'Hello'),
    ]);

    $builder->paused(new InvokableClass);

    expect($builder->getCallbacks()->onPaused)->toEqual([
        new SerializableClosure(fn () => 'Hello'),
        new SerializableClosure(fn () => new InvokableClass),
    ]);
});

test('you can specify middleware as a closure, invokable class or an array', function () {
    $builder = new HaystackBuilder;

    $builder->addMiddleware(fn () => [new Middleware()]);

    expect($builder->getMiddleware()->data)->toEqual([
        new SerializableClosure(fn () => [new Middleware()]),
    ]);

    $builder->addMiddleware(new InvokableMiddleware);

    expect($builder->getMiddleware()->data)->toEqual([
        new SerializableClosure(fn () => [new Middleware()]),
        new SerializableClosure(fn () => new InvokableMiddleware),
    ]);

    $builder->addMiddleware([new Middleware]);

    expect($builder->getMiddleware()->data)->toEqual([
        new SerializableClosure(fn () => [new Middleware()]),
        new SerializableClosure(fn () => new InvokableMiddleware),
        new SerializableClosure(fn () => [new Middleware()]),
    ]);

    // Now we'll try to get all the middleware, it should give us a nice array of them all

    $allMiddleware = $builder->getMiddleware()->toMiddlewareArray();

    expect($allMiddleware)->toEqual([
        new Middleware,
        new Middleware,
        new Middleware,
    ]);
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

test('it will respect the delay, connection and queue added to jobs if not set', function () {
    $builder = new HaystackBuilder;

    $builder->withDelay(120);
    $builder->onQueue('cowboy');
    $builder->onConnection('redis');

    $job = new NameJob('Sam');

    $job->delay(60);
    $job->onConnection('database');
    $job->onQueue('testing');

    $builder->addJob($job);

    $jobs = $builder->getJobs();

    expect($jobs[0]->delayInSeconds)->toEqual(60);
    expect($jobs[0]->queue)->toEqual('testing');
    expect($jobs[0]->connection)->toEqual('database');
});

test('job specified delay, connection or queue on a per job basis which takes priority over globals', function () {
    $builder = new HaystackBuilder;

    $builder->withDelay(120);
    $builder->onQueue('cowboy');
    $builder->onConnection('redis');

    $job = new NameJob('Sam');

    $job->delay(60);
    $job->onConnection('database');
    $job->onQueue('testing');

    $builder->addJob($job);

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

test('you can use conditional clauses when building your haystack', function () {
    $builder = new HaystackBuilder;
    $neilJob = new NameJob('Neil');

    $builder->when(true, function ($haystack) use ($neilJob) {
        $haystack->addJob($neilJob);
    })->when(false, function ($haystack) {
        $haystack->withDelay(30);
    }, function ($haystack) {
        $haystack->withDelay(50);
    })->when(true, function ($haystack) {
        $haystack->onConnection('database');
    });

    $jobs = $builder->getJobs();

    expect($jobs)->toHaveCount(1);
    expect($jobs[0]->job)->toEqual($neilJob);

    expect($builder->getGlobalDelayInSeconds())->toEqual(50);
    expect($builder->getGlobalConnection())->toEqual('database');
});
