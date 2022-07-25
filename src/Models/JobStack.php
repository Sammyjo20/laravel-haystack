<?php

namespace Sammyjo20\LaravelJobStack\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobStack extends Model
{
    use HasFactory;

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $casts = [
        //
    ];

    /**
     * The JobStack's rows.
     *
     * @return HasMany
     */
    public function rows(): HasMany
    {
        return $this->hasMany(JobStackRow::class, 'job_stack_id', 'id')->orderBy('id', 'asc');
    }
}
