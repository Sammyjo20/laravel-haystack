<?php

namespace Sammyjo20\LaravelHaystack\Models;

use Illuminate\Database\Eloquent\Model;
use Sammyjo20\LaravelHaystack\Casts\SerializeJob;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Sammyjo20\LaravelHaystack\Database\Factories\HaystackBaleFactory;
use Sammyjo20\LaravelHaystack\Database\Factories\HaystackDataFactory;

class HaystackData extends Model
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
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return HaystackDataFactory::new();
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
     * Set the cast attribute and apply the casts.
     *
     * @param string|null $cast
     * @return void
     */
    public function setCastAttribute(?string $cast): void
    {
        if (! is_null($cast)) {
            $this->casts = ['value' => $cast];
        }

        $this->attributes['cast'] = $cast;
    }
}
