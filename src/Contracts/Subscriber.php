<?php

namespace GPX\EventBus\Contracts;

use GPX\EventBus\Subscription;
use GPX\EventBus\Worker\Event;

interface Subscriber
{
    public function subscribedTo(): Subscription;

    public function handle(Event $event): void;
}
