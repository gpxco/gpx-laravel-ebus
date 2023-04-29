<?php

namespace Tests\Unit\Broadcaster;

use GPX\EventBus\Broadcaster\Event;
use Carbon\Carbon;
use Tests\TestCase;

class EventTest extends TestCase
{
    public function test_event_object_can_return_message_fields()
    {
        $now = Carbon::parse('-1 hour');
        $eventName = 'event-name';
        $key = 'key-name';
        $eventAt = $now;
        $payload = ['id' => 1];

        $event = new Event($eventName, $payload, $key, $eventAt);

        $this->assertEquals($eventName, $event->getEventName());
        $this->assertEquals($key, $event->getKey());
        $this->assertEquals($now->toAtomString(), $event->getEventAt()->toAtomString());
        $this->assertEquals((object) $payload, $event->getPayload());
    }
}
