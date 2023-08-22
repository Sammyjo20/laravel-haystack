<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoRetryUntilJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\RetryUntilJob;
use function Pest\Laravel\travel;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AlwaysLongReleaseJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoAlwaysLongReleaseJob;

test('it will record the attempts/times job has been run and fail when it reaches the limit', function (StackableJob $job, bool $autoProcessing) {
    withJobsTable();
    dontDeleteHaystack();

    config()->set('queue.default', 'database');

    if ($autoProcessing === true) {
        withAutomaticProcessing();
    }

    $haystack = Haystack::build()
        ->addJob($job)
        ->catch(function () {
            config()->set('failed', true);
        })
        ->create();

    $bale = $haystack->bales()->sole();

    // We'll manually dispatch the job three times and on the third
    // attempt, it should throw an exception because the $tries
    // is set to 2.

    expect($bale->attempts)->toEqual(0);

    $haystack->start();

    $this->artisan('queue:work', ['--once' => true]);

    $bale->refresh();

    expect($bale->attempts)->toEqual(1);

    travel(10)->seconds();

    $this->artisan('haystacks:resume');

    $this->artisan('queue:work', ['--once' => true]);
    $bale->refresh();

    expect($bale->attempts)->toEqual(2);

    travel(10)->seconds();

    $this->artisan('haystacks:resume');

    $this->artisan('queue:work', ['--once' => true]);
    $bale->refresh();

    expect($bale->attempts)->toEqual(2);

    $haystack->refresh();

    expect($haystack->finished)->toBeTrue();
    expect(config()->get('failed'))->toBeTrue();
})->with([
    [new AlwaysLongReleaseJob(5), false],
    [new AutoAlwaysLongReleaseJob(5), true],
]);

test('when using retry until the job will continue running until the time has passed', function (StackableJob $job, bool $autoProcessing) {
    Carbon::setTestNow('2023-01-01 15:00:00');

    withJobsTable();
    dontDeleteHaystack();

    config()->set('queue.default', 'database');

    if ($autoProcessing === true) {
        withAutomaticProcessing();
    }

    $haystack = Haystack::build()
        ->addJob($job)
        ->catch(function () {
            config()->set('failed', true);
        })
        ->create();

    // The job will have a retry until for 30 minutes - so we will
    // manually retry the job and it should work until we travel
    // over thirty minutes.

    $bale = $haystack->bales()->sole();

    expect($bale->retry_until)->toBeNull();
    expect($bale->attempts)->toEqual(0);

    $haystack->start();

    $this->artisan('queue:work', ['--once' => true]);

    $bale->refresh();

    $threeThirty = now()->addMinutes(30)->timestamp;

    expect($bale->retry_until)->toEqual($threeThirty);
    expect($bale->attempts)->toEqual(1);

    travel(10)->seconds();

    $this->artisan('haystacks:resume');

    $this->artisan('queue:work', ['--once' => true]);
    $bale->refresh();

    expect($bale->retry_until)->toEqual($threeThirty);
    expect($bale->attempts)->toEqual(2);

    travel(10)->seconds();

    $this->artisan('haystacks:resume');

    $this->artisan('queue:work', ['--once' => true]);
    $bale->refresh();

    expect($bale->retry_until)->toEqual($threeThirty);
    expect($bale->attempts)->toEqual(3);

    // Now we'll advance to 35 minutes and it should fail

    travel(35)->minutes();

    $this->artisan('haystacks:resume');

    $this->artisan('queue:work', ['--once' => true]);
    $bale->refresh();

    $haystack->refresh();

    expect($haystack->finished)->toBeTrue();
    expect(config()->get('failed'))->toBeTrue();
})->with([
    [new RetryUntilJob(5), false],
    [new AutoRetryUntilJob(5), true],
]);
