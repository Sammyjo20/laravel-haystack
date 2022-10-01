<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Data;

use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class SerializedModel
{
    use SerializesModels;

    public function __construct(public Model $model)
    {
        //
    }
}
