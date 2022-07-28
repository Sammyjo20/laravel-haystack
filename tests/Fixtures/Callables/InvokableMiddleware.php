<?php

namespace Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables;

class InvokableMiddleware
{
    public function __invoke(): array
    {
        return [
            new Middleware(),
        ];
    }
}
