<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Tests\Fixtures\Callables;

class CounterMiddleware
{
    public function handle($command, $next)
    {
        cache()->increment('count');

        $next($command);
    }
}
