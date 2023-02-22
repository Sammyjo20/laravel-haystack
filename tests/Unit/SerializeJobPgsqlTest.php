<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Models\HaystackBale;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;

beforeEach(function () {
    $this->setupForPgSqlTest();
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

    // For pgsql, the serialized string is additionally base64 encoded
    $this->assertEquals(
        'Tzo1MzoiU2FtbXlqbzIwXExhcmF2ZWxIYXlzdGFja1xUZXN0c1xGaXh0dXJlc1xKb2JzXE5hbWVKb2IiOjE6e3M6NDoibmFtZSI7czo0OiJuYW1lIjt9',
        $serialized_string
    );
});
