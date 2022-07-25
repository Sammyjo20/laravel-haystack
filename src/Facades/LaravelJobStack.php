<?php

namespace Sammyjo20\LaravelJobStack\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Sammyjo20\LaravelJobStack\LaravelJobStack
 */
class LaravelJobStack extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-job-stack';
    }
}
