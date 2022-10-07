<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables;

class InvokableCounterMiddleware
{
    public function __invoke(): array
    {
        return [new CounterMiddleware];
    }
}
