<?php

use Illuminate\Support\Collection;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Models\HaystackData;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Jobs\NameJob;

test('a haystack data row can belong to a haystack', function () {
    $haystack = Haystack::factory()->create();

    $haystackData = HaystackData::factory()->for($haystack)->create([
        'key' => 'name',
        'cast' => null,
        'value' => 'Sam',
    ]);

    expect($haystackData->haystack_id)->toEqual($haystack->getKey());
    expect($haystackData->haystack->getKey())->toEqual($haystack->getKey());
});

test('a haystack data row can cast the data when retrieving', function () {
    $haystackData = HaystackData::factory()->for(Haystack::factory())->create([
        'key' => 'data',
        'cast' => 'collection',
        'value' => ['name' => 'Sam', 'age' => 22],
    ]);

    expect($haystackData->value)->toBeInstanceOf(Collection::class);
    expect($haystackData->value)->toEqual(new Collection(['name' => 'Sam', 'age' => 22]));
});

test('a haystack data row can cast the data to a custom cast when retrieving', function () {
    //
});

test('a haystack data row can cast the data to a dto when retrieving', function () {
    //
});
