<?php

declare(strict_types=1);

use Sammyjo20\LaravelHaystack\Models\HaystackBale;

test('you can pass null to the serializes job cast', function () {
    $bale = new HaystackBale;

    $bale->job = null;

    expect($bale->job)->toBeNull();
});
