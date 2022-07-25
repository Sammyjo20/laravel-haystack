<?php

namespace Sammyjo20\LaravelJobStack\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Sammyjo20\LaravelJobStack\Builders\JobStackBuilder;
use Sammyjo20\LaravelJobStack\Casts\SerializeClosure;
use Sammyjo20\LaravelJobStack\Casts\SerializeJob;
use Sammyjo20\LaravelJobStack\Concerns\ManagesJobs;

class JobStack extends Model
{
    use HasFactory;
    use ManagesJobs;

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $casts = [
        'on_then' => SerializeClosure::class,
        'on_catch' => SerializeClosure::class,
        'on_finally' => SerializeClosure::class,
        'intermediate_job' => SerializeJob::class,
        'middleware' => SerializeClosure::class,
        'started' => 'boolean',
        'finished' => 'boolean',
    ];

    /**
     * @var false[]
     */
    protected $attributes = [
        'started' => false,
        'finished' => false,
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

    /**
     * Start building a JobStack.
     *
     * @return JobStackBuilder
     */
    public static function build(): JobStackBuilder
    {
        return new JobStackBuilder;
    }
}
