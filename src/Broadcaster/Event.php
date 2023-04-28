<?php

namespace GPX\EventBus\Broadcaster;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class Event
{
    protected string $eventName;

    protected Carbon $eventAt;

    protected object $payload;

    protected ?string $key;

    public function __construct(string $eventName, object|array $payload, ?string $key = null, Carbon $eventAt = null)
    {
        $this->eventName = $eventName;
        $this->key = $key ?: (is_array($payload) ? Arr::get($payload, 'id') : $payload->id ?? null);
        $this->eventAt = $eventAt ?: Carbon::now();
        $this->payload = (object) $payload;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getEventAt(): Carbon
    {
        return $this->eventAt;
    }

    public function getPayload(): object
    {
        return $this->payload;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }
}
