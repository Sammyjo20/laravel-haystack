<?php

use function Pest\Laravel\travel;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AlwaysLongReleaseJob;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoAlwaysLongReleaseJob;

test('it will record the attempts/times job has been run and fail when it reaches the limit', function (StackableJob $job, bool $autoProcessing) {
    dontDeleteHaystack();

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

    expect($bale->attempts)->toEqual(0);

    $haystack->start();

    $bale->refresh();

    expect($bale->attempts)->toEqual(1);

    travel(10)->seconds();

    $this->artisan('haystacks:resume');

    $bale->refresh();

    expect($bale->attempts)->toEqual(2);

    travel(10)->seconds();

    $this->artisan('haystacks:resume');

    $bale->refresh();

    expect($bale->attempts)->toEqual(3);

    travel(10)->seconds();

    $this->artisan('haystacks:resume');

    $haystack->refresh();

    expect($haystack->finished)->toBeTrue();
    expect(config()->get('failed'))->toBeTrue();
})->with([
    [new AlwaysLongReleaseJob(5), false],
    [new AutoAlwaysLongReleaseJob(5), true],
]);
