<?php

namespace Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables;

class Middleware
{
    public function handle($command, $next)
    {
        ray('Howdy!');

        $next($command);
    }
}
