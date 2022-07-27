<?php

namespace Sammyjo20\LaravelJobStack\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sammyjo20\LaravelJobStack\Casts\SerializeJob;
use Sammyjo20\LaravelJobStack\Database\Factories\JobStackRowFactory;

class JobStackRow extends Model
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
        'job' => SerializeJob::class,
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return JobStackRowFactory::new();
    }

    /**
     * The JobStack this row belongs to.
     *
     * @return BelongsTo
     */
    public function jobStack(): BelongsTo
    {
        return $this->belongsTo(JobStack::class);
    }
}
