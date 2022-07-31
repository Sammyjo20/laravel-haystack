<?php

use Illuminate\Support\Carbon;
use Sammyjo20\LaravelHaystack\LaravelHaystackServiceProvider;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoCacheJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoLongReleaseJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoPauseNextJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\CacheJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\LongReleaseJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\PauseNextJob;
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
    withAutomaticProcessing();

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

test('you can pause the next job for a long time and it will be picked up with an artisan command', function () {
    Carbon::setTestNow('2022-01-01 09:00');

    $pauseDate = now()->addMinutes(5);

    $haystack = Haystack::build()
        ->addJob(new PauseNextJob('name', 'Sam', 300))
        ->addJob(new CacheJob('developer', 'Taylor'))
        ->dispatch();

    expect($haystack->resume_at)->toBeNull();

    $haystack->refresh();

    expect($haystack->resume_at)->toEqual($pauseDate);
    expect(cache()->get('name'))->toEqual('Sam');
    expect(cache()->has('developer'))->toBeFalse();

    // We'll try to run "dispatchNextJob" and it won't work...

    $this->artisan('resume:haystacks');

    expect($haystack->resume_at)->toEqual($pauseDate);
    expect(cache()->get('name'))->toEqual('Sam');
    expect(cache()->has('developer'))->toBeFalse();

    // Now we'll time travel

    travel(5)->minutes();

    $this->artisan('resume:haystacks');

    expect(cache()->get('name'))->toEqual('Sam');
    expect(cache()->get('developer'))->toEqual('Taylor');
});

test('you can pause the next job for a long time and it will be picked up with an artisan command with automatic mode on', function () {
    withAutomaticProcessing();

    Carbon::setTestNow('2022-01-01 09:00');

    $pauseDate = now()->addMinutes(5);

    $haystack = Haystack::build()
        ->addJob(new AutoPauseNextJob('name', 'Sam', 300))
        ->addJob(new AutoCacheJob('developer', 'Taylor'))
        ->dispatch();

    expect($haystack->resume_at)->toBeNull();

    $haystack->refresh();

    expect($haystack->resume_at)->toEqual($pauseDate);
    expect(cache()->get('name'))->toEqual('Sam');
    expect(cache()->has('developer'))->toBeFalse();

    // We'll try to run "dispatchNextJob" and it won't work...

    $this->artisan('resume:haystacks');

    expect($haystack->resume_at)->toEqual($pauseDate);
    expect(cache()->get('name'))->toEqual('Sam');
    expect(cache()->has('developer'))->toBeFalse();

    // Now we'll time travel

    travel(5)->minutes();

    $this->artisan('resume:haystacks');

    expect(cache()->get('name'))->toEqual('Sam');
    expect(cache()->get('developer'))->toEqual('Taylor');
});
