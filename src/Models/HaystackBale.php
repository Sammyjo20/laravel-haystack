<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Models;

use Illuminate\Database\Eloquent\Model;
use Sammyjo20\LaravelHaystack\Casts\Serialized;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'job' => Serialized::class,
        'priority' => 'boolean',
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
    public function haystack(): BelongsTo
    {
        return $this->belongsTo(Haystack::class);
    }

    /**
     * Get the job already configured.
     *
     * @return StackableJob
     */
    public function configuredJob(): StackableJob
    {
        $job = $this->job;

        if ($this->delay > 0) {
            $job->delay($this->delay);
        }

        if (filled($this->on_queue)) {
            $job->onQueue($this->on_queue);
        }

        if (filled($this->on_connection)) {
            $job->onConnection($this->on_connection);
        }

        return $job;
    }

    /**
     * Get the current connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return config('haystack.db_connection');
    }
}
