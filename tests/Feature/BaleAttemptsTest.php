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

    // Todo: Run on database so exceptions aren't thrown 🤠

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

it('it will record the attempts/times job has been had an exception and fail when it reaches the limit', function () {
    // This will only work with automatic processing turned on, because
    // we have to listen for events.

    withAutomaticProcessing();

    //
});
