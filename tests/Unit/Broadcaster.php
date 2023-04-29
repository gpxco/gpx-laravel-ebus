<?php

namespace Tests\Unit\GPX\EventBus;

use GPX\EventBus\Broadcaster\BroadcastOptions;
use DanikDantist\QueueWrapper\Manager;
use DanikDantist\QueueWrapper\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class Broadcaster extends TestCase
{
    public function test_that_we_send_to_kafka_right_message_via_fireObjectUpdateEvent_method()
    {
        $id = 2222;
        $expectedTime = Carbon::parse('-3 days');
        Carbon::setTestNow($expectedTime);
        $broadcastMessage = [
            'test' => 'test2',
        ];
        $eventName = 'saved';
        $expectedObjectName = Str::lower('Mockery_1_App_Services_EventBus_Contracts_BroadcastableObject');
        $expectedName = $expectedObjectName.':'.$eventName;
        $expectedKey = $expectedObjectName.':'.$id;
        $eventQueue = 'events';
        $expectedMessage = [
            'name' => $expectedName,
            'key' => $expectedKey,
            'eventAt' => $expectedTime->toDateTimeLocalString(),
            'payload' => $broadcastMessage,
        ];

        $mockKafka = \Mockery::mock(Manager::class)->makePartial();

        $mockKafka->shouldReceive('sendMessage')->withArgs(function (Message $arg) use ($expectedKey, $eventQueue, $expectedMessage) {
            return $arg->getKey() == $expectedKey &&
                $arg->getTopicName() == $eventQueue &&
                $arg->toString() == json_encode($expectedMessage);
        })->once();

        $mockMessage = \Mockery::mock(\GPX\EventBus\Contracts\BroadcastableObject::class);
        $mockMessage->shouldReceive('toBroadcast')->andReturn($broadcastMessage);
        $mockMessage->shouldReceive('getEventKey')->andReturn($expectedKey);
        $mockMessage->shouldReceive('getObjectName')->andReturn($expectedObjectName);

        $opt = new BroadcastOptions();
        $opt->serviceQueueName = $eventQueue;
        $broadcaster = new \GPX\EventBus\Broadcaster($mockKafka, $opt);

        $broadcaster->fireObjectEvent($eventName, $mockMessage, $expectedTime);

        $this->assertTrue(true);
    }

    public function test_that_we_send_to_kafka_right_message_via_fireBatchObjectUpdateEvent_method()
    {
        $id = 2222;
        $expectedTime = Carbon::parse('-3 days');
        Carbon::setTestNow($expectedTime);
        $broadcastMessage = [
            'test' => 'test2',
        ];
        $eventName = 'saved';
        $expectedObjectName = Str::lower('Mockery_1_App_Services_EventBus_Contracts_BroadcastableObject');
        $expectedName = $expectedObjectName.':'.$eventName;
        $expectedKey = $expectedObjectName.':'.$id;
        $eventQueue = 'events';
        $expectedMessage = [
            'name' => $expectedName,
            'key' => $expectedKey,
            'eventAt' => $expectedTime->toDateTimeLocalString(),
            'payload' => $broadcastMessage,
        ];

        $mockKafka = \Mockery::mock(Manager::class)->makePartial();

        $mockKafka->shouldReceive('addMessage')->withArgs(function (Message $arg) use ($expectedKey, $eventQueue, $expectedMessage) {
            return $arg->getKey() == $expectedKey &&
                $arg->getTopicName() == $eventQueue &&
                $arg->toString() == json_encode($expectedMessage);
        })->once();
        $mockKafka->shouldReceive('flush')->once();

        $mockMessage = \Mockery::mock(\GPX\EventBus\Contracts\BroadcastableObject::class);
        $mockMessage->shouldReceive('toBroadcast')->andReturn($broadcastMessage);
        $mockMessage->shouldReceive('getEventKey')->andReturn($expectedKey);
        $mockMessage->shouldReceive('getObjectName')->andReturn($expectedObjectName);

        $opt = new BroadcastOptions();
        $opt->serviceQueueName = $eventQueue;
        $broadcaster = new \GPX\EventBus\Broadcaster($mockKafka, $opt);

        $broadcaster->fireBatchObjectEvent($eventName, [$mockMessage], $expectedTime);

        $this->assertTrue(true);
    }
}
