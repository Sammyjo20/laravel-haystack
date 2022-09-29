<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Database\Factories;

use Sammyjo20\LaravelHaystack\Data\HaystackOptions;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use Illuminate\Database\Eloquent\Factories\Factory;

class HaystackFactory extends Factory
{
    protected $model = Haystack::class;

    /**
     * Definition
     *
     * @return array|mixed[]
     */
    public function definition()
    {
        return [
            'options' => new HaystackOptions,
        ];
    }
}
