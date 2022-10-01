<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Sammyjo20\LaravelHaystack\Data\SerializedModel as SerializedModelData;

class SerializedModel implements CastsAttributes
{
    /**
     * Unserialize a job.
     *
     * @param $model
     * @param  string  $key
     * @param $value
     * @param  array  $attributes
     * @return mixed|null
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return isset($value) ? unserialize($value, ['allowed_classes' => true])->model : null;
    }

    /**
     * Serialize a model.
     *
     * @param $model
     * @param  string  $key
     * @param $value
     * @param  array  $attributes
     * @return mixed|string|null
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if (blank($value)) {
            return null;
        }

        if (! $value instanceof Model) {
            throw new InvalidArgumentException('The provided value must be a model.');
        }

        return serialize(new SerializedModelData($value));
    }
}
