<?php

use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Tests\Exceptions\StackableException;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\AutoPauseNextJob;

test('you can provide seconds or a carbon instance into the delay methods', function () {

});

test('it will throw an exception if you try to use the pauseHaystack method when automatic_processing is turned off', function () {
    $this->expectException(StackableException::class);
    $this->expectExceptionMessage('The "pauseHaystack" method is unavailable when "haystack.process_automatically" is disabled. Use the "nextJob" with a delay provided instead.');

    Haystack::build()
        ->addJob(new AutoPauseNextJob('name', 'Sam', 300))
        ->dispatch();
});
