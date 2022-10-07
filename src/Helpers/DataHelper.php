<?php

namespace Sammyjo20\LaravelHaystack\Helpers;

use Illuminate\Database\Eloquent\Model;

class DataHelper
{
    /**
     * Create the model key
     *
     * @param Model $model
     * @param string|null $key
     * @return string
     */
    public static function getModelKey(Model $model, string $key = null): string
    {
        return 'model:'.($key ?? $model::class);
    }
}
