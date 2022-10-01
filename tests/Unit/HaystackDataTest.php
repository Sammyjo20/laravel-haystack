<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Sammyjo20\LaravelHaystack\Models\HaystackData;
use Sammyjo20\LaravelHaystack\Tests\Fixtures\Models\CountrySinger;

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

test('models stored in haystack data wont be returned when retrieving all data', function () {
    $migration = include __DIR__.'/../Migrations/create_country_singers_table.php';
    $migration->up();

    $countrySinger = CountrySinger::create(['name' => 'Kenny Rogers']);

    $haystack = Haystack::build()
        ->withModel($countrySinger)
        ->create();

    $haystackData = $haystack->data()->sole();

    expect($haystackData->value)->toBeInstanceOf(Model::class);
    expect($haystackData->value)->toEqual($countrySinger->fresh());

    // Let's also make sure the data does not include the model by default

    $data = $haystack->allData();

    expect($data)->toHaveCount(0);

    $data = $haystack->allData(true);

    expect($data)->toHaveCount(1);
    expect($data['model:' . CountrySinger::class])->toEqual($countrySinger->fresh());
});
