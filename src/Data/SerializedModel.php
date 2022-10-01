<?php

namespace Sammyjo20\LaravelHaystack\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class SerializedModel
{
    use SerializesModels;

    public function __construct(public Model $model)
    {
        //
    }
}
