<?php

namespace GPX\EventBus\Worker;

use GPX\EventBus\Contracts\Subscriber;
use GPX\EventBus\Subscription;
use DanikDantist\QueueWrapper;
use DanikDantist\QueueWrapper\Interfaces\iReceivable;

class Receiver implements iReceivable
{
    protected Subscription $subscription;

    protected bool $allEvents = false;

    public function __construct(protected Subscriber $subscriber)
    {
        $this->subscription = $subscriber->subscribedTo();
        $this->allEvents = in_array('*', $this->subscription->getEvents());
    }

    public function receiveMessage(QueueWrapper\Message $message)
    {
        if ($this->subscription->getQueueName() === $message->getTopicName()) {
            $event = new Event(json_decode($message->toString()));
            if ($this->allEvents) {
                $this->subscriber->handle($event);
            } else {
                foreach ($this->subscription->getEvents() as $eventName) {
                    if ($event->getName() === $eventName) {
                        $this->subscriber->handle($event);
                    }
                }
            }
        }
    }
}
