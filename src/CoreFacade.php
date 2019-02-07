<?php

namespace Reddes\MappableModels;

use \Illuminate\Support\Facades\Facade;

class CoreFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mappable-models';
    }
}
