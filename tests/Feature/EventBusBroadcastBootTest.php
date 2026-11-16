<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\Fixtures\BroadcastableStub;

/**
 * Smoke test for EventBusBroadcast on Laravel 13+: constructing a model with
 * the trait must not hit the boot re-entrancy LogicException (new static during boot).
 */
class EventBusBroadcastBootTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::clearBootedModels();
    }

    public function test_model_with_event_bus_broadcast_boots_without_logic_exception(): void
    {
        try {
            $model = new BroadcastableStub(['name' => 'probe']);
        } catch (LogicException $e) {
            $this->fail('EventBusBroadcast boot must not re-enter model boot: '.$e->getMessage());
        }

        $this->assertInstanceOf(BroadcastableStub::class, $model);
        $this->assertSame('probe', $model->name);
    }

    public function test_second_instance_reuses_booted_trait_without_logic_exception(): void
    {
        new BroadcastableStub(['name' => 'first']);
        $second = new BroadcastableStub(['name' => 'second']);

        $this->assertSame('second', $second->name);
    }
}
