<?php

namespace Tests\Unit\Worker;

use GPX\EventBus\Worker\Event;
use Carbon\Carbon;
use Tests\TestCase;

class EventTest extends TestCase
{
    public function test_event_object_can_return_message_fields()
    {
        $now = Carbon::parse('-1 hour');
        $eventName = 'event-name';
        $key = 'key-name';
        $eventAt = $now->toAtomString();
        $payload = ['id' => 1];
        $message = [
            'name' => $eventName,
            'key' => $key,
            'eventAt' => $eventAt,
            'payload' => $payload,
        ];

        $event = new Event(json_decode(json_encode($message)));

        $this->assertEquals($eventName, $event->getName());
        $this->assertEquals($key, $event->getKey());
        $this->assertEquals($now->toAtomString(), $event->getEventAt()->toAtomString());
        $this->assertEquals((object) $payload, $event->getPayload());
    }

    public function test_event_object_can_be_created_without_event_time()
    {
        $now = Carbon::parse('-1 hour');
        Carbon::setTestNow($now);

        $message = [
            'name' => 'event-name',
            'key' => 'key-name',
            'payload' => ['id' => 1],
        ];

        $event = new Event(json_decode(json_encode($message)));

        $this->assertEquals($now->toAtomString(), $event->getEventAt()->toAtomString());
    }

    public function test_event_object_be_created_without_key()
    {
        $message = [
            'name' => 'event-name',
            'eventAt' => Carbon::parse('-1 hour')->toAtomString(),
            'payload' => ['id' => 1],
        ];

        $event = new Event(json_decode(json_encode($message)));

        $this->assertEquals(null, $event->getKey());
    }

    public function test_event_object_can_be_created_without_payload()
    {
        $message = [
            'name' => 'event-name',
            'key' => 'key-name',
            'eventAt' => Carbon::parse('-1 hour'),
        ];

        $event = new Event(json_decode(json_encode($message)));

        $this->assertEquals((object) null, $event->getPayload());
    }
}
