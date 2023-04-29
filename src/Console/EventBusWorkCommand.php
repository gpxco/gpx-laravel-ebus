<?php

namespace GPX\EventBus\Console;

use GPX\EventBus\WorkerProcess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class EventBusWorkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event-bus:work {--subscribers= : This is an optional parameter. If you want to handle subscribers separately that are not specified in the EventBusServiceProvider, you can use this parameter by listing the subscribers separated by commas. } {--consumer_name= : This is an optional parameter that controls the consumer group.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a background job to subscribe to events from other services. By default, if you run the command without any options, the subscribers listed in the EventBusServiceProvider will be started.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        /** @var WorkerProcess $worker */
        $worker = app(WorkerProcess::class);

        if ($this->option('subscribers')) {
            $this->warn('Using the --subscribers= and --consumer_name= options requires you to know what you are doing! Be careful, as improper usage can result in data loss.');
            if (App::environment(['production', 'staging']) && ! $this->confirm('Do you wish to continue?')) {
                return;
            }

            if (! $this->option('consumer_name')) {
                $this->error('Command option `--consumer_name=` should be used with `--subscribers=` option');

                return;
            }

            $worker->setConsumerName($this->option('consumer_name'));

            $subscriptionString = $this->option('subscribers');
            $listenersNames = explode(',', $subscriptionString);
            $subscribers = [];
            foreach ($listenersNames as $name) {
                $name = trim($name);
                $defaultSubscriber = '\\App\\Listeners\\'.$name;
                if (class_exists($defaultSubscriber)) {
                    $subscribers[] = $defaultSubscriber;
                } elseif (class_exists($name)) {
                    $subscribers[] = $name;
                } else {
                    $this->error('Can\'t find listener '.$defaultSubscriber.' or '.$name);

                    return;
                }
            }

            if (! $subscribers) {
                $this->error('Please provide list of listeners or use command without --listeners option');

                return;
            }
            $worker->setSubscribers($subscribers); //replaced default listeners
        }
        $this->info('EventBus start listening...');
        if ($worker->run() == 1) {
            $this->info('EventBus listener have no subscribers. Check App\Providers\EventBusServiceProvider.');
        }
    }
}
