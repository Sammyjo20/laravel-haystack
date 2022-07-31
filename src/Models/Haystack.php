<?php

namespace Sammyjo20\LaravelHaystack\Models;

use Illuminate\Database\Eloquent\Model;
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
        'middleware' => SerializeClosure::class,
        'started' => 'boolean',
        'finished' => 'boolean',
        'started_at' => 'immutable_datetime',
        'resume_at' => 'immutable_datetime',
        'finished_at' => 'immutable_datetime',
    ];

    /**
     * @var false[]
     */
    protected $attributes = [
        'started' => false,
        'finished' => false,
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
     * The Haystack's bales.
     *
     * @return HasMany
     */
    public function bales(): HasMany
    {
        return $this->hasMany(HaystackBale::class, 'haystack_id', 'id')->orderBy('id', 'asc');
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
}
