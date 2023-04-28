<?php

namespace GPX\EventBus;

use GPX\EventBus\Contracts\Subscriber;
use DanikDantist\QueueWrapper\Drivers\Kafka\Config as KafkaConfig;
use DanikDantist\QueueWrapper\Drivers\Kafka\Connector;
use DanikDantist\QueueWrapper\Manager;
use Illuminate\Support\ServiceProvider;

class EventBusServiceProvider extends ServiceProvider
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
        $this->app->singleton(Broadcaster::class, function () {
            $config = new KafkaConfig();
            $config->addBroker(config('gpx-event-bus.kafka.broker-list', ''));
            $config->setKafkaRawConfig(config('gpx-event-bus.kafka.producer-config', []));
            $logger = app(config('gpx-event-bus.logger', Logger::class));
            $queueManager = new Manager(new Connector($config, $logger));
            $options = new Broadcaster\BroadcastOptions();
            $options->serviceQueueName = config('gpx-event-bus.service.outgoing-events-queue-name', '');

            return new Broadcaster($queueManager, $options);
        });

        $this->app->bind(WorkerProcess::class, function () {
            $config = new KafkaConfig();
            $config->addBroker(config('gpx-event-bus.kafka.broker-list', ''));
            $config->setKafkaRawConfig(config('gpx-event-bus.kafka.consumer-config', []));
            $logger = app(config('gpx-event-bus.logger', Logger::class));
            $demon = new Manager(new Connector($config, $logger));
            $options = new Worker\WorkerOptions();
            $options->serviceConsumerName = config('gpx-event-bus.service.consumer-name', '');

            return new WorkerProcess($demon, $options, array_map(function ($subscriberName) {
                return app($subscriberName);
            }, $this->subscribers));
        });

        $this->offerPublishing();
        $this->registerCommands();
    }

    /**
     * Register the Artisan commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\EventBusWorkCommand::class,
            ]);
        }
    }

    /**
     * Setup the resource publishing groups for EventBus.
     *
     * @return void
     */
    protected function offerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../stubs/EventBusServiceProvider.stub' => app_path('Providers/EventBusServiceProvide.php'),
            ], 'event-bus-provider');

            $this->publishes([
                __DIR__.'/../config/gpx-event-bus.php' => config_path('gpx-event-bus.php'),
            ], 'event-bus-config');
        }
    }
}
