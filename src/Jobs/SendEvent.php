<?php

namespace GPX\EventBus\Jobs;

use GPX\EventBus\Broadcaster;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected string $eventName,
        protected Carbon $eventAt,
        protected int $modelId,
        protected string $modelClass,
    ) {
    }

    public function handle()
    {
        $model = $this->getModel();
        if (!$model) {
            return;
        }
        /** @var Broadcaster $broadcaster */
        $broadcaster = app(Broadcaster::class);

        $broadcaster->fireObjectEvent($this->eventName, $model, $this->eventAt);
        \Log::debug('SEND EVENT '.$this->eventName.' $model: '.$this->modelClass);
    }

    protected function getModel()
    {
        if (method_exists($this->modelClass, 'bootSoftDeletes')) {
            return $this->modelClass::withTrashed()->find($this->modelId);
        } else {
            return $this->modelClass::find($this->modelId);
        }
    }
}
