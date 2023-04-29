<?php

namespace Tests\Unit\GPX\EventBus\Worker;

use GPX\EventBus\Contracts\Subscriber;
use GPX\EventBus\Subscription;
use GPX\EventBus\Worker\Event;
use GPX\EventBus\Worker\Receiver;
use DanikDantist\QueueWrapper;
use Tests\TestCase;

class ReceiverTest extends TestCase
{
    public function test_subscriber_will_run_handle_method_for_certain_event_and_queue()
    {
        $topicName = 'topic-name';
        $eventName = 'event-name';
        $message = [
            'name' => $eventName,
        ];
        $events = [$eventName];
        $message = new QueueWrapper\Message(json_encode($message), $topicName);

        $mockSubscription = \Mockery::mock(Subscription::class)->makePartial();
        $mockSubscription->shouldReceive('getQueueName')->andReturn($topicName);
        $mockSubscription->shouldReceive('getEvents')->andReturn($events);

        $mockSubscriber = \Mockery::mock(Subscriber::class)->makePartial();
        $mockSubscriber->shouldReceive('handle')->withArgs(function (Event $event) use ($eventName) {
            return $event->getName() == $eventName;
        })->once();
        $mockSubscriber->shouldReceive('subscribedTo')->andReturn($mockSubscription);

        $receiver = new Receiver($mockSubscriber);
        $receiver->receiveMessage($message);

        $this->assertTrue(true);
    }

    public function test_subscriber_will_not_run_handle_method_for_different_event()
    {
        $topicName = 'topic-name';
        $eventName = 'event-name';
        $message = [
            'name' => $eventName,
        ];
        $events = ['another-event'];
        $message = new QueueWrapper\Message(json_encode($message), $topicName);

        $mockSubscription = \Mockery::mock(Subscription::class)->makePartial();
        $mockSubscription->shouldReceive('getQueueName')->andReturn($topicName);
        $mockSubscription->shouldReceive('getEvents')->andReturn($events);

        $mockSubscriber = \Mockery::mock(Subscriber::class)->makePartial();
        $mockSubscriber->shouldReceive('handle')->never();
        $mockSubscriber->shouldReceive('subscribedTo')->andReturn($mockSubscription);

        $receiver = new Receiver($mockSubscriber);
        $receiver->receiveMessage($message);

        $this->assertTrue(true);
    }

    public function test_subscriber_will_not_run_handle_method_for_different_queue()
    {
        $topicName = 'topic-name';
        $eventName = 'event-name';
        $message = [
            'name' => $eventName,
        ];
        $events = [$eventName];
        $message = new QueueWrapper\Message(json_encode($message), $topicName);

        $mockSubscription = \Mockery::mock(Subscription::class)->makePartial();
        $mockSubscription->shouldReceive('getQueueName')->andReturn('different_topic');
        $mockSubscription->shouldReceive('getEvents')->andReturn($events);

        $mockSubscriber = \Mockery::mock(Subscriber::class)->makePartial();
        $mockSubscriber->shouldReceive('handle')->never();
        $mockSubscriber->shouldReceive('subscribedTo')->andReturn($mockSubscription);

        $receiver = new Receiver($mockSubscriber);
        $receiver->receiveMessage($message);

        $this->assertTrue(true);
    }
}
