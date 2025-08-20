## GPX event bus

## How to install it

First you need to add this section to composer.json in your application
```shell
...
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:gpxco/gpx-laravel-ebus.git"
    }
],
...
```
Then you need to install package
```shell
composer require "gpxco/gpx-laravel-ebus"
```
Don't forget to define required .env variable
```dotenv
#Name of your microservice
SERVICE_NAME=engine

KAFKA_BROKER_LIST=gpx-kafka:9092
```

## How to produce event

### 1. Use model trait

```php
<?php

namespace App\Models;

use GPX\EventBus\Contracts\BroadcastableObject;
use GPX\EventBus\EventBusOptions;
use GPX\EventBus\Traits\EventBusBroadcast;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MyModel extends Model implements BroadcastableObject
{
    use EventBusBroadcast;
    
    protected $fillable = ['my_field'];
    
    //This array will be sent as a payload.
    public function toBroadcast(): array
    {
        return [
            'id' => $this->id,
            'my_field' => $this->my_field,
        ];
    }

    // This method subscribes us to changes in the "my_field" attribute,
    // and if "my_field" changes, we will send an event to the queue.
    public static function getEventBusOptions(): EventBusOptions
    {
        return EventBusOptions::defaults()
            ->watchAttributes([
                'my_field',
            ]);
    }
}
```
Or extended example of EventBusOptions
```php
class Account extends Model implements BroadcastableObject
{
    use EventBusBroadcast;
    
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
    
    public function toBroadcast(): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'subscription_status' => $this->subscription?->status,
        ];
    }
    
    public static function getEventBusOptions(): EventBusOptions
    {
        return EventBusOptions::defaults()
            ->watchAttributes([
                'my_field',
            ])
            ->watchRelations([
                //We can also listen for changes to fields in our related models,
                //we just need to specify the path to the field.
                //In this example, we are listening for changes to the 'status' field
                //in the related 'Subscription' model.
                //Additionally, we need to specify as the second parameter
                //how to get back to our 'Account' model from the 'Subscription' model.
                Relation::d('subscription.status', 'accounts'),
            ])
            //The 'with' parameter is used to list which relationships need to be fetched when the 'toBroadcast' method is called.
            //This allows us to optimize database queries.
            ->with(['subscription'])
    }
}
```
### 2. Use custom event
```php
use GPX\EventBus\Broadcaster;

/** @var Broadcaster $broadcaster */
$broadcaster = app(Broadcaster::class);
$broadcaster->fireEvent(
    new Broadcaster\Event('device:saved', ['id' => $deviceId, 'account_id' => $accountId], 'device:'.$deviceId, Carbon::now())
);

```

## How to subscribe on event
Also, if you need to create subscribers or change config, please install it
```shell
php artisan event-bus:install
```

Then you can add subscribers to your application

```php
<?php

namespace App\Listeners;

use GPX\EventBus\Contracts\Subscriber;
use GPX\EventBus\Subscription;
use GPX\EventBus\Worker\Event;

class DeviceEngineSubscriber implements Subscriber
{
    public function subscribedTo(): Subscription
    {
        return new Subscription('engine_events', ['device:saved']);
    }

    public function handle(Event $event): void
    {
        //handle event
    }
}
```

And then register your Subscriber inside the service provider

```php
<?php

namespace App\Providers;

use App\Listeners\DeviceEngineSubscriber;
use GPX\EventBus\EventBusApplicationServiceProvider as ServiceProvider;
use GPX\EventBus\Contracts\Subscriber;

class EventBusServiceProvider extends ServiceProvider
{
    /**
     * The subscriber classes to register.
     *
     * @var array<Subscriber::class> - list of Subscriber class name
     */
    protected array $subscribers = [
        DeviceEngineSubscriber::class,
    ];
}

```