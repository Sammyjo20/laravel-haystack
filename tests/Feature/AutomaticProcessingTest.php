<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\CacheJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\ReleaseJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoCacheJob;
use Sammyjo20\LaravelHaystack\Tests\Exceptions\StackableException;

beforeEach(function () {
    withAutomaticProcessing();
});

test('it throws an exception if you try to queue the next job with automatic queuing turned on', function () {
    $this->expectException(StackableException::class);
    $this->expectExceptionMessage('The "nextJob" method is unavailable when "haystack.process_automatically" is enabled.');

    Haystack::build()
        ->addJob(new CacheJob('name', 'Sam'))
        ->dispatch();
});

test('it can process jobs automatically', function () {
    Haystack::build()
        ->addBale(new AutoCacheJob('name', 'Sam'))
        ->addBale(new AutoCacheJob('friend', 'Michael'))
        ->addJob(new AutoCacheJob('boss', 'Gareth'))
        ->dispatch();

    expect(cache()->get('name'))->toEqual('Sam');
    expect(cache()->get('friend'))->toEqual('Michael');
    expect(cache()->get('boss'))->toEqual('Gareth');
});

test('if a job is released it will not be processed', function () {
    Queue::fake([
        ReleaseJob::class,
    ]);

    $haystack = Haystack::build()
        ->addBale(new AutoCacheJob('name', 'Sam'))
        ->addBale(new AutoCacheJob('friend', 'Michael'))
        ->addBale(new ReleaseJob)
        ->addJob(new AutoCacheJob('boss', 'Gareth'))
        ->dispatch();

    expect(cache()->get('name'))->toEqual('Sam');
    expect(cache()->get('friend'))->toEqual('Michael');
    expect(cache()->get('boss'))->toBeNull();

    Queue::assertPushed(ReleaseJob::class);

    // We'll make sure that Gareth's job is still queued.

    $bales = $haystack->bales()->get();

    expect($bales)->toHaveCount(2);
    expect($bales[0]->job)->toEqual(new ReleaseJob);
    expect($bales[1]->job)->toEqual(new AutoCacheJob('boss', 'Gareth'));
});

test('if a job is using the sync connection - we will not stop if it was released', function () {
    Haystack::build()
        ->addBale(new ReleaseJob)
        ->addJob(new AutoCacheJob('boss', 'Gareth'))
        ->onConnection('sync')
        ->dispatch();

    expect(cache()->get('boss'))->toEqual('Gareth');
});

test('if a job is using the database connection - we will not process the next job if it is released', function () {
    Carbon::setTestNow('2022-01-01 09:00:00');

    Haystack::build()
        ->addJob(new ReleaseJob)
        ->addJob(new AutoCacheJob('boss', 'Gareth'))
        ->dispatch();gi

    expect(cache()->get('boss'))->toBeNull();

    $jobs = DB::table('jobs')->get();

    expect($jobs)->toHaveCount(1);

    $job = $jobs[0];

    dd($job);

    expect($job->available_at)->toEqual(now()->addSeconds(10)->timestamp);
});
