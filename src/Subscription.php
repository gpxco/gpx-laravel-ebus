<?php

namespace GPX\EventBus;

class Subscription
{
    public function __construct(protected string $queueName, protected array $events = ['*'])
    {
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function getEvents(): array
    {
        return $this->events;
    }
}
