<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables;

class InvokableClass
{
    public function __invoke()
    {
        return 'Howdy!';
    }
}
