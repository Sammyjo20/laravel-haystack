<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Casts;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Sammyjo20\LaravelHaystack\Data\SerializedModel as SerializedModelData;
use Sammyjo20\LaravelHaystack\Helpers\SerializationHelper;

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
        return isset($value) ? SerializationHelper::unserialize($value, ['allowed_classes' => true])->model : null;
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

        return SerializationHelper::serialize(new SerializedModelData($value));
    }
}
