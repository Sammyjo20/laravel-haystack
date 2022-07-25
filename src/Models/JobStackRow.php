<?php

namespace Sammyjo20\LaravelJobStack\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sammyjo20\LaravelJobStack\Casts\SerializeJob;

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
     * The JobStack this row belongs to.
     *
     * @return BelongsTo
     */
    public function jobStack(): BelongsTo
    {
        return $this->belongsTo(JobStack::class);
    }
}
