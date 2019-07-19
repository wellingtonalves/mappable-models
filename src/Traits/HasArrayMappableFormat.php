<?php

namespace Reddes\MappableModels\Traits;


trait HasArrayMappableFormat
{
    /**
     * @return mixed
     */
    public function toArray()
    {
        if (method_exists($this, 'getMaps')) {

            if (count($this->getMaps()) === 0) {
                return parent::toArray();
            }

            return $this->getRemappedColumns();
        }

        return parent::toArray();
    }

    private function getRemappedColumns()
    {
        $mapped = [];
        $flippedMaps = array_flip($this->getMaps());
        foreach (parent::toArray() as $index => $data) {
            // TODO colocar aqui a opção de verificar se está em caixa alta o valor da index de acordo com a config
            $index = strtoupper($index);
            if (array_key_exists($index, $flippedMaps)) {
                $mapped[$flippedMaps[$index]] = $data;
            }
        }
        return $mapped;
    }
}
