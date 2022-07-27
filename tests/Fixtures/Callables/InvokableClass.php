<?php

namespace Sammyjo20\LaravelJobStack\Tests\Fixtures\Callables;

class InvokableClass
{
    public function __invoke()
    {
        return 'Howdy!';
    }
}
