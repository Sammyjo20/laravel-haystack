<?php

namespace Sammyjo20\LaravelHaystack\Models;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sammyjo20\LaravelHaystack\Casts\SerializeJob;
use Sammyjo20\LaravelHaystack\Database\Factories\HaystackBaleFactory;

class HaystackBale extends Model
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
        return HaystackBaleFactory::new();
    }

    /**
     * The Haystack this row belongs to.
     *
     * @return BelongsTo
     */
    public function jobStack(): BelongsTo
    {
        return $this->belongsTo(Haystack::class);
    }

    /**
     * Get the job already configured.
     *
     * @return ShouldQueue
     */
    public function configuredJob(): ShouldQueue
    {
        $job = $this->job;

        if ($this->delay > 0) {
            $job->delay($this->delay);
        }

        if (filled($this->on_queue)) {
            $job->onQueue($this->on_queue);
        }

        if (filled($this->connection)) {
            $job->onConnection($this->on_connection);
        }

        return $job;
    }
}
