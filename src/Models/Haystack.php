<?php

namespace Sammyjo20\LaravelHaystack\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Sammyjo20\LaravelHaystack\Concerns\ManagesBales;
use Sammyjo20\LaravelHaystack\Casts\SerializeClosure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Sammyjo20\LaravelHaystack\Builders\HaystackBuilder;
use Sammyjo20\LaravelHaystack\Database\Factories\HaystackFactory;

class Haystack extends Model
{
    use HasFactory;
    use ManagesBales;
    use Prunable;

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
        'on_paused' => SerializeClosure::class,
        'middleware' => SerializeClosure::class,
        'started' => 'boolean',
        'finished' => 'boolean',
        'started_at' => 'immutable_datetime',
        'resume_at' => 'immutable_datetime',
        'finished_at' => 'immutable_datetime',
        'return_data' => 'boolean',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return HaystackFactory::new();
    }

    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable(): Builder
    {
        $staleHaystackDays = config('haystack.keep_stale_haystacks_for_days', 0);
        $finishedHaystackDays = config('haystack.keep_finished_haystacks_for_days', 0);

        return static::query()
            ->where(function ($query) use ($staleHaystackDays) {
                $query->whereNull('finished_at')->where('started_at', '<=', now()->subDays($staleHaystackDays));
            })
            ->orWhere(function ($query) use ($finishedHaystackDays) {
                $query->whereNotNull('started_at')->where('finished_at', '<=', now()->subDays($finishedHaystackDays));
            });
    }

    /**
     * The Haystack's bales.
     *
     * @return HasMany
     */
    public function bales(): HasMany
    {
        return $this->hasMany(HaystackBale::class, 'haystack_id', 'id')->orderBy('id', 'asc');
    }

    /**
     * The Haystack's data.
     *
     * @return HasMany
     */
    public function data(): HasMany
    {
        return $this->hasMany(HaystackData::class);
    }

    /**
     * Start building a Haystack.
     *
     * @return HaystackBuilder
     */
    public static function build(): HaystackBuilder
    {
        return new HaystackBuilder;
    }

    /**
     * Denotes if the haystack has started.
     *
     * @return bool
     */
    public function getStartedAttribute(): bool
    {
        return $this->started_at instanceof CarbonImmutable;
    }

    /**
     * Denotes if the haystack has finished.
     *
     * @return bool
     */
    public function getFinishedAttribute(): bool
    {
        return $this->finished_at instanceof CarbonImmutable;
    }
}
