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
     * @var array<Subscriber::class> - list of Subscriber class name
     */
    protected array $subscribers = [
    ];
    
    public function register()
    {
        $this->booting(function () {
            /** @var WorkerProcess $service */
            $service = $this->app->get(WorkerProcess::class);
            $service->setSubscribers($this->subscribers);
        });
    }
}
