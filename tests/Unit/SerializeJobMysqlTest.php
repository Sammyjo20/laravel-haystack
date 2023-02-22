<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Models\HaystackBale;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;

beforeEach(function () {
    $this->setupForMySqlTest();
});

test('you can pass model and it will be serialized', function () {
    $haystack = Haystack::factory()->create();

    $bale = new HaystackBale;

    $bale->job = new NameJob('name');
    $bale->haystack()->associate($haystack);
    $bale->save();

    $serialized_string = (string) DB::table('haystack_bales')
        ->select('job')
        ->first()
        ->job;

    // For mysql, the serialized string is saved as-is
    $this->assertEquals(
        'O:53:"Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob":1:{s:4:"name";s:4:"name";}',
        $serialized_string
    );
});
