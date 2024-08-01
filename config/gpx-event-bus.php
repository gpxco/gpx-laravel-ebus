<?php

use Illuminate\Support\Str;

$serviceName = Str::snake(env('SERVICE_NAME', env('APP_NAME')));

return [
    /*
    |--------------------------------------------------------------------------
    | Kafka Options
    |--------------------------------------------------------------------------
    */
    'kafka' => [
        'broker-list' => env('KAFKA_BROKER_LIST'),
        'producer-config' => [
            'receive.message.max.bytes' => 52428800 + 512,
            'topic.metadata.refresh.sparse' => true,
            'topic.metadata.refresh.interval.ms' => 600000,
            'socket.send.buffer.bytes' => 1000000,
            'queue.buffering.max.messages' => 10000000,
        ],
        'consumer-config' => [
            'receive.message.max.bytes' => 52428800 + 512,
            'topic.metadata.refresh.sparse' => true,
            'topic.metadata.refresh.interval.ms' => 600000,
            'socket.send.buffer.bytes' => 1000000,
            //'auto.offset.reset' => 'earliest',//latest, earliest. By default latest
        ],
    ],
    'logger' => GPX\EventBus\Logger::class,
    'service' => [
        'name' => $serviceName,
        /*
        |--------------------------------------------------------------------------
        | Event Queue
        |--------------------------------------------------------------------------
        |
        | This option controls the topic name of outgoing events
        |
        */
        'outgoing-events-queue-name' => env('EVENTBUS_SERVICE_OUTGOING_EVENTS_QUEUE_NAME', $serviceName.'_events'),
        /*
       |
       | This option controls the group of consumer name
       |
       */
        'consumer-name' => env('EVENTBUS_SERVICE_CONSUMER_NAME', $serviceName),
    ],
    'queue' => [
        'timeout' => 300,
        'name' => 'default'
    ]
];
