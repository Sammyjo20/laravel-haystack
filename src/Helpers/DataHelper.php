<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Helpers;

use Illuminate\Database\Eloquent\Model;

class DataHelper
{
    /**
     * Create the model key
     */
    public static function getModelKey(Model $model, string $key = null): string
    {
        return 'model:'.($key ?? $model::class);
    }
}
