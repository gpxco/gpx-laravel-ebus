<?php

namespace GPX\EventBus\Facades;

use Illuminate\Support\Facades\Facade;

class Broadcaster extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\EventBus\Broadcaster::class;
    }
}
