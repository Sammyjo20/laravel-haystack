<?php

use Illuminate\Support\Facades\Queue;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\CacheJob;
use Sammyjo20\LaravelHaystack\LaravelHaystackServiceProvider;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\ReleaseJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoCacheJob;
use Sammyjo20\LaravelHaystack\Tests\Exceptions\StackableException;

beforeEach(function () {
    config()->set('haystack.process_automatically', true);

    // It's a bit hacky, but we'll run the "bootingPackage" method
    // on the provider to  start recording events.

    (new LaravelHaystackServiceProvider(app()))->bootingPackage();
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

    expect($bales)->toHaveCount(1);
    expect($bales[0]->job)->toEqual(new AutoCacheJob('boss', 'Gareth'));
});
