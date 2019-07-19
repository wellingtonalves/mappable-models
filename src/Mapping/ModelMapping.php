<?php

namespace Reddes\MappableModels\Mapping;

class ModelMapping
{
    private $content = [];

    public function __construct(string $key)
    {
        $file = new ModelMappingFile();
        $this->content = $file->getContent($key);
    }

    public static function isEnabled()
    {
        return config('mappable-models.model_mapping.enabled');
    }

    public function getTable(): string
    {
        return array_get($this->content, 'table');
    }

    public function getPrimaryKey(): string
    {
        return array_get($this->content, 'primary');
    }

    public function getMappings(): array
    {
        $mapping = array_get($this->content, 'columns');
        if (config('mappable-models.model_mapping.uppercase')) {
            return array_map(function ($item) {
                return strtoupper($item);
            }, $mapping);
        }

        return $mapping;
    }

    public function getSequence(): string
    {
        return array_get($this->content, 'sequence');
    }
}
