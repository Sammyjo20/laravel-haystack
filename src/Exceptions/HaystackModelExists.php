<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Exceptions;

use Exception;
use Illuminate\Support\Str;

class HaystackModelExists extends Exception
{
    /**
     * Constructor
     *
     * @param  string  $key
     */
    public function __construct(string $key)
    {
        $key = Str::remove('model:', $key);

        parent::__construct(sprintf('Model with the key "%s" has already been defined on the Haystack. Use the second argument to define a custom key.', $key));
    }
}
