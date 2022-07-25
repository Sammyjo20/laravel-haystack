<?php

namespace Sammyjo20\LaravelJobStack\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Sammyjo20\LaravelJobStack\Builders\JobStackBuilder;
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
