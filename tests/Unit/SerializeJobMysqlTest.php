<?php

declare(strict_types=1);

use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Models\HaystackBale;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;

beforeEach(function () {
    $this->setupForMySqlTest();
});

test('you can pass model and it will be serialized/deserialized correctly', function () {
    $haystack = Haystack::factory()->create();

    $bale = new HaystackBale;

    $bale->job = new NameJob('name');
    $bale->haystack()->associate($haystack);
    $bale->save();

    // For mysql, the serialized string is saved as-is
    $this->assertEquals(
        new NameJob('name'),
        HaystackBale::first()->job
    );
});
