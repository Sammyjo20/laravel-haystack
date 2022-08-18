<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\PauseNextJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\LongReleaseJob;
use Sammyjo20\LaravelHaystack\Tests\Exceptions\StackableException;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoPauseNextJob;

test('you can provide a carbon instance into the longRelease method', function () {
    Carbon::setTestNow('2022-01-01 09:00');

    $releaseUntil = now()->addMinutes(5);

    cache()->set('release', true);

    $haystack = Haystack::build()
        ->addJob(new LongReleaseJob($releaseUntil))
        ->dispatch();

    $haystack->refresh();

    expect($haystack->resume_at)->toEqual($releaseUntil);
});

test('you can provide seconds into the longRelease method', function () {
    Carbon::setTestNow('2022-01-01 09:00');

    $releaseUntil = now()->addMinutes(5);

    cache()->set('release', true);

    $haystack = Haystack::build()
        ->addJob(new LongReleaseJob(300))
        ->dispatch();

    $haystack->refresh();

    expect($haystack->resume_at)->toEqual($releaseUntil);
});

test('you can provide a carbon instance into the nextJob method', function () {
    Carbon::setTestNow('2022-01-01 09:00');

    $releaseUntil = now()->addMinutes(5);

    cache()->set('release', true);

    $haystack = Haystack::build()
        ->addJob(new PauseNextJob('name', 'Sam', $releaseUntil))
        ->dispatch();

    $haystack->refresh();

    expect($haystack->resume_at)->toEqual($releaseUntil);
});

test('you can provide seconds into the nextJob method', function () {
    Carbon::setTestNow('2022-01-01 09:00');

    $releaseUntil = now()->addMinutes(5);

    cache()->set('release', true);

    $haystack = Haystack::build()
        ->addJob(new PauseNextJob('name', 'Sam', 300))
        ->dispatch();

    $haystack->refresh();

    expect($haystack->resume_at)->toEqual($releaseUntil);
});

test('you can provide a carbon instance into the pauseHaystack method', function () {
    withAutomaticProcessing();

    Carbon::setTestNow('2022-01-01 09:00');

    $releaseUntil = now()->addMinutes(5);

    cache()->set('release', true);

    $haystack = Haystack::build()
        ->addJob(new AutoPauseNextJob('name', 'Sam', $releaseUntil))
        ->dispatch();

    $haystack->refresh();

    expect($haystack->resume_at)->toEqual($releaseUntil);
});

test('you can provide seconds into the pauseHaystack method', function () {
    withAutomaticProcessing();

    Carbon::setTestNow('2022-01-01 09:00');

    $releaseUntil = now()->addMinutes(5);

    cache()->set('release', true);

    $haystack = Haystack::build()
        ->addJob(new AutoPauseNextJob('name', 'Sam', 300))
        ->dispatch();

    $haystack->refresh();

    expect($haystack->resume_at)->toEqual($releaseUntil);
});

test('it will throw an exception if you try to use the pauseHaystack method when automatic_processing is turned off', function () {
    $this->expectException(StackableException::class);
    $this->expectExceptionMessage('The "pauseHaystack" method is unavailable when "haystack.process_automatically" is disabled. Use the "nextJob" with a delay provided instead.');

    Haystack::build()
        ->addJob(new AutoPauseNextJob('name', 'Sam', 300))
        ->dispatch();
});
