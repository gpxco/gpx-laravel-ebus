<?php

namespace Tests\Fixtures;

use GPX\EventBus\Contracts\BroadcastableObject;
use GPX\EventBus\EventBusOptions;
use GPX\EventBus\Traits\EventBusBroadcast;
use Illuminate\Database\Eloquent\Model;

class BroadcastableStub extends Model implements BroadcastableObject
{
    use EventBusBroadcast;

    public $timestamps = false;

    protected $guarded = [];

    protected $table = 'broadcastable_stubs';

    public function toBroadcast(): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
        ];
    }

    public static function getEventBusOptions(): EventBusOptions
    {
        return EventBusOptions::defaults()
            ->watchAttributes(['name']);
    }
}
