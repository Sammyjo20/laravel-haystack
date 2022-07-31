<?php

namespace Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables;

use function Pest\Laravel\travel;

class TravelMiddleware
{
    public function handle($command, $next)
    {
        travel(5)->minutes();

        $next($command);
    }
}
