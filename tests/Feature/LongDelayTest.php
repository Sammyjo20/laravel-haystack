<?php

use Sammyjo20\LaravelHaystack\LaravelHaystackServiceProvider;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoCacheJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoLongReleaseJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\CacheJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\LongReleaseJob;
use function Pest\Laravel\travel;

test('you can release the current job for a long time and will be picked up with an artisan command', function () {
    $releaseUntil = now()->addMinutes(5)->toImmutable();

    cache()->set('release', true);

    $haystack = Haystack::build()
        ->addJob(new LongReleaseJob($releaseUntil))
        ->addJob(new CacheJob('name', 'Sam'))
        ->dispatch();

    expect($haystack->resume_at)->toBeNull();
    expect(Haystack::query()->count())->toEqual(1);
    expect($haystack->bales()->count())->toEqual(2);

    $haystack->refresh();

    expect($haystack->resume_at->toIso8601String())->toEqual($releaseUntil->toIso8601String());
    expect($haystack->bales()->count())->toEqual(2);
    expect(cache()->has('longReleaseFinished'))->toBeFalse();
    expect(cache()->has('name'))->toBeFalse();

    // Now we'll try to run the resume command but, it's not time yet, it shouldn't do anything.

    $this->artisan('resume:haystacks');

    expect($haystack->resume_at->toIso8601String())->toEqual($releaseUntil->toIso8601String());
    expect($haystack->bales()->count())->toEqual(2);
    expect(cache()->has('longReleaseFinished'))->toBeFalse();
    expect(cache()->has('name'))->toBeFalse();

    travel(5)->minutes();

    $this->artisan('resume:haystacks');

    // We'll now check

    expect(cache()->has('longReleaseFinished'))->toBeTrue();
    expect(cache()->get('name'))->toEqual('Sam');

    // The haystack should also be deleted

    expect(Haystack::query()->count())->toEqual(0);
});

test('you can release the current job for a long time and will be picked up with an artisan command with automatic mode on', function () {
    automaticProcessing();

    $releaseUntil = now()->addMinutes(5)->toImmutable();

    cache()->set('release', true);

    $haystack = Haystack::build()
        ->addJob(new AutoLongReleaseJob($releaseUntil))
        ->addJob(new AutoCacheJob('name', 'Sam'))
        ->dispatch();

    expect($haystack->resume_at)->toBeNull();
    expect(Haystack::query()->count())->toEqual(1);
    expect($haystack->bales()->count())->toEqual(2);

    $haystack->refresh();

    expect($haystack->resume_at->toIso8601String())->toEqual($releaseUntil->toIso8601String());
    expect($haystack->bales()->count())->toEqual(2);
    expect(cache()->has('longReleaseFinished'))->toBeFalse();
    expect(cache()->has('name'))->toBeFalse();

    // Now we'll try to run the resume command but, it's not time yet, it shouldn't do anything.

    $this->artisan('resume:haystacks');

    expect($haystack->resume_at->toIso8601String())->toEqual($releaseUntil->toIso8601String());
    expect($haystack->bales()->count())->toEqual(2);
    expect(cache()->has('longReleaseFinished'))->toBeFalse();
    expect(cache()->has('name'))->toBeFalse();

    travel(5)->minutes();

    $this->artisan('resume:haystacks');

    // We'll now check

    expect(cache()->has('longReleaseFinished'))->toBeTrue();
    expect(cache()->get('name'))->toEqual('Sam');

    // The haystack should also be deleted

    expect(Haystack::query()->count())->toEqual(0);
});

test('you can delay the next job for a long time and it will be picked up with an artisan command', function () {
    // 1. Check that the processed job no longer exists
    // 2. Check that the delayed time is accurate
    // 3. Run the command first and make sure it is not dispatched
    // 4. Time travel and run command again
    // 5. Assert it was run
});
