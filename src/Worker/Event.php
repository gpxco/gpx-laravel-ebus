<?php

namespace GPX\EventBus\Worker;

use Carbon\Carbon;

class Event
{
    protected string $name;

    protected ?string $key;

    protected Carbon $eventAt;

    protected object $payload;

    public function __construct(object $event)
    {
        $this->name = $event->name;
        $this->key = $event->key ?? null;
        $this->eventAt = Carbon::parse($event->eventAt ?? 'now');
        $this->payload = (object) ($event->payload ?? null);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getEventAt(): Carbon
    {
        return $this->eventAt;
    }

    public function getPayload(): object
    {
        return $this->payload;
    }
}
