<?php

namespace GPX\EventBus;

use GPX\EventBus\Broadcaster\Event;
use GPX\EventBus\Contracts\BroadcastableObject;
use Carbon\Carbon;
use DanikDantist\QueueWrapper\Manager;
use DanikDantist\QueueWrapper\Message as QueueMessage;
use Illuminate\Support\Collection;

class Broadcaster
{
    protected bool $canSend = true;

    public function __construct(protected Manager $queueManager, protected Broadcaster\BroadcastOptions $broadcastOptions)
    {
        if (! env('KAFKA_BROKER_LIST')) {
            $this->canSend = false;
        }
    }

    public function fireEvent(Event $event): void
    {
        if (! $this->canSend) {
            \Log::error('We can not send event. Please check environment `KAFKA_BROKER_LIST`');

            return;
        }
        try {
            $message = $this->buildEventMessage($event->getEventName(), $event->getKey(), $event->getPayload(), $event->getEventAt());
            $this->queueManager->sendMessage(
                new QueueMessage(
                    $this->packMessageToString($message),
                    $this->broadcastOptions->serviceQueueName,
                    $event->getKey()
                )
            );
        } catch (\Exception $e) {
            \Log::error($e->getMessage()."\n".$e->getTraceAsString());
        }
    }

    public function fireObjectEvent(string $eventName, BroadcastableObject $object, Carbon $eventAt = null): void
    {
        if (! $this->canSend) {
            \Log::error('We can not send event. Please check environment `KAFKA_BROKER_LIST`');

            return;
        }
        try {
            $now = $eventAt ?: Carbon::now();
            $event = $this->buildEventMessageByObject($eventName, $object, $now);
            $this->queueManager->sendMessage(new QueueMessage(json_encode($event), $this->broadcastOptions->serviceQueueName, $object->getEventKey()));
        } catch (\Exception $e) {
            \Log::error($e->getMessage()."\n".$e->getTraceAsString());
        }
    }

    public function fireBatchObjectEvent(string $eventName, array|Collection $objects, Carbon $eventAt = null): void
    {
        if (! $this->canSend) {
            \Log::error('We can not send event. Please check environment `KAFKA_BROKER_LIST`');

            return;
        }
        try {
            $now = $eventAt ?: Carbon::now();
            foreach ($objects as $object) {
                if ($object instanceof BroadcastableObject) {
                    $event = $this->buildEventMessageByObject($eventName, $object, $now);
                    $this->queueManager->addMessage(new QueueMessage(json_encode($event), $this->broadcastOptions->serviceQueueName, $object->getEventKey()));
                }
            }
            $this->queueManager->flush();
        } catch (\Exception $e) {
            \Log::error($e->getMessage()."\n".$e->getTraceAsString());
        }
    }

    protected function buildEventMessageByObject(string $eventName, BroadcastableObject $object, Carbon $eventAt): array
    {
        return $this->buildEventMessage(
            $object->getObjectName().':'.$eventName,
            $object->getEventKey(),
            $object->toBroadcast(),
            $eventAt
        );
    }

    protected function buildEventMessage(string $eventName, ?string $keyName, object|array $payload, Carbon $eventAt): array
    {
        return [
            'name' => $eventName,
            'key' => $keyName,
            'eventAt' => $eventAt->toDateTimeLocalString(),
            'payload' => $payload,
        ];
    }

    protected function packMessageToString(array $message): string
    {
        return json_encode($message);
    }
}
