<?php

namespace GPX\EventBus\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event-bus:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the GPX Event Bus resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing GPX Event Bus Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'event-bus-provider']);

        $this->comment('Publishing GPX Event Bus Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'event-bus-config']);

        $this->registerEventBusServiceProvide();

        $this->info('GPX Event Bus scaffolding installed successfully.');
    }

    protected function registerEventBusServiceProvide()
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace.'\\Providers\\EventBusServiceProvider::class')) {
            return;
        }

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL,
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL."        {$namespace}\Providers\EventBusServiceProvider::class,".PHP_EOL,
            $appConfig
        ));

        file_put_contents(app_path('Providers/EventBusServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/EventBusServiceProvider.php'))
        ));
    }
}
