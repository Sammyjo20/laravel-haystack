<?php

namespace Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables;

class InvokableClass
{
    public function __invoke()
    {
        return 'Howdy!';
    }
}
