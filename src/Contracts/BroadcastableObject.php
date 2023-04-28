<?php

namespace GPX\EventBus\Contracts;

interface BroadcastableObject
{
    /**
     * Payload of event
     */
    public function toBroadcast(): array;

    public function getObjectName(): string;

    public function getEventKey(): string;
}
