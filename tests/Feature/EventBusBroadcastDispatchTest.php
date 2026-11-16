<?php

namespace Tests\Feature;

use GPX\EventBus\Broadcaster;
use GPX\EventBus\Contracts\BroadcastableObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Orchestra\Testbench\TestCase;
use Tests\Fixtures\BroadcastableStub;

/**
 * Trait + toBroadcast: a watched attribute change must call Broadcaster with
 * the payload from the model's toBroadcast().
 */
class EventBusBroadcastDispatchTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Model::clearBootedModels();

        Schema::create('broadcastable_stubs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
        });
    }

    public function test_to_broadcast_returns_id_and_watched_attributes(): void
    {
        $model = new BroadcastableStub([
            'id' => 42,
            'name' => 'alpha',
        ]);

        $this->assertSame(
            ['id' => 42, 'name' => 'alpha'],
            $model->toBroadcast()
        );
    }

    public function test_saving_a_watched_attribute_change_fires_broadcaster_with_to_broadcast_payload(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-15 12:00:00'));

        $captured = [];
        $broadcaster = Mockery::mock(Broadcaster::class);
        $broadcaster
            ->shouldReceive('fireObjectEvent')
            ->andReturnUsing(function (string $eventName, BroadcastableObject $object, $eventAt = null) use (&$captured) {
                $captured[] = [
                    'event' => $eventName,
                    'payload' => $object->toBroadcast(),
                    'key' => $object->getEventKey(),
                ];
            });
        $this->app->instance(Broadcaster::class, $broadcaster);

        $model = new BroadcastableStub(['name' => 'before']);
        $model->save();

        $model->name = 'after';
        $model->save();

        $this->assertCount(2, $captured, 'create and update of watched `name` must each fire once');

        $this->assertSame('saved', $captured[0]['event']);
        $this->assertSame(['id' => 1, 'name' => 'before'], $captured[0]['payload']);
        $this->assertSame('broadcastablestub:1', $captured[0]['key']);

        $this->assertSame('saved', $captured[1]['event']);
        $this->assertSame(['id' => 1, 'name' => 'after'], $captured[1]['payload']);
        $this->assertSame('broadcastablestub:1', $captured[1]['key']);
        $this->assertSame(['id' => 1, 'name' => 'after'], $model->toBroadcast());
    }

    public function test_saving_without_watched_attribute_change_does_not_fire_broadcaster(): void
    {
        $broadcaster = Mockery::mock(Broadcaster::class);
        $broadcaster->shouldReceive('fireObjectEvent')->once(); // create only
        $this->app->instance(Broadcaster::class, $broadcaster);

        $model = BroadcastableStub::query()->create(['name' => 'same']);
        $model->save(); // no dirty watched attributes

        $this->assertSame(['id' => 1, 'name' => 'same'], $model->toBroadcast());
    }
}
