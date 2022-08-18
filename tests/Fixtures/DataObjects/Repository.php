<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Tests\Fixtures\DataObjects;

use JessArcher\CastableDataTransferObject\CastableDataTransferObject;

class Repository extends CastableDataTransferObject
{
    public readonly string $name;

    public readonly string $stars;

    public readonly string $isLaravel;
}
