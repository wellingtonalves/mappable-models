<?php

namespace Reddes\MappableModels;

use Reddes\MappableModels\Mapping\ModelMapping;
use Reddes\MappableModels\Traits\HasArrayMappableFormat;
use Reddes\MappableModels\Traits\HasAutomaticMapping;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class MappableModel extends Model
{
    use Eloquence, Mappable, HasAutomaticMapping, HasArrayMappableFormat {
        HasArrayMappableFormat::toArray insteadof Eloquence;
    }

    protected $sequence;

    public function __construct(array $attributes = [])
    {
        // Map columns if mapping is enabled
        $this->mapModel();

        // After map columns construct parent
        parent::__construct($attributes);
    }

    private function mapModel()
    {
        if (ModelMapping::isEnabled()) {
            $mm = new ModelMapping($this->getTable());

            $this->table = $mm->getTable();
            $this->primaryKey = $mm->getPrimaryKey();
            $this->maps = $mm->getMappings();
            $this->sequence = $mm->getSequence();
        }
    }
}
