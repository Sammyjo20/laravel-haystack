<?php

namespace Sammyjo20\LaravelHaystack\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Sammyjo20\LaravelHaystack\Database\Factories\HaystackDataFactory;

class HaystackData extends Model
{
    use HasFactory;

    /**
     * @var array
     */
    protected $guarded = [];

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
     * @param  string|null  $cast
     * @return void
     */
    public function setCastAttribute(?string $cast): void
    {
        if (! is_null($cast)) {
            $this->casts = ['value' => $cast];
        }

        $this->attributes['cast'] = $cast;
        $this->attributes['value'] = null;
    }

    /**
     * Get the cast value.
     *
     * @param $value
     * @return mixed
     */
    public function getValueAttribute($value): mixed
    {
        if (blank($this->cast)) {
            return $value;
        }

        // We'll now manually add the cast and attempt to cast the attribute.

        $this->casts = ['value' => $this->cast];

        return $this->castAttribute('value', $value);
    }
}
