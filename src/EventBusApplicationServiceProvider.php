<?php

namespace GPX\EventBus;

use GPX\EventBus\Contracts\Subscriber;
use DanikDantist\QueueWrapper\Drivers\Kafka\Config as KafkaConfig;
use DanikDantist\QueueWrapper\Drivers\Kafka\Connector;
use DanikDantist\QueueWrapper\Manager;
use Illuminate\Support\ServiceProvider;

class EventBusApplicationServiceProvider extends ServiceProvider
{
    /**
     * The subscriber classes to register.
     *
     * @var array<Subscriber>
     */
    protected array $subscribers = [
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

    }

}
