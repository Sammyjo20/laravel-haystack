<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Models\HaystackData;

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

    $haystackData->refresh();

    expect($haystackData->value)->toBeInstanceOf(Collection::class);
    expect($haystackData->value)->toEqual(new Collection(['name' => 'Sam', 'age' => 22]));
});
