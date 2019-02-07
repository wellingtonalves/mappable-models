<?php

namespace Reddes\MappableModels\Traits;

trait HasAutomaticMapping
{
    /**
     * @var array
     */
    protected $maps = [];

    /**
     * Get the name of the "updated at" column.
     *
     * @return string
     */
    public function getUpdatedAtColumn()
    {
        if ($value = array_get($this->maps, 'updated_at')) {
            return $value;
        }

        return static::UPDATED_AT;
    }

    /**
     * Get the name of the "created at" column.
     *
     * @return string
     */
    public function getCreatedAtColumn()
    {
        if ($value = array_get($this->maps, 'created_at')) {
            return $value;
        }

        return static::CREATED_AT;
    }
}
