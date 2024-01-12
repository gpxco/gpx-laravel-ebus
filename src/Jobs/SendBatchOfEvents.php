<?php

namespace GPX\EventBus\Jobs;

use GPX\EventBus\Broadcaster;
use GPX\EventBus\Contracts\BroadcastableObject;
use GPX\EventBus\Helpers\ModelRelations;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBatchOfEvents implements ShouldQueue
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
        protected string $path,
        protected array $with = [],
        protected array $where = []
    ) {
    }

    public function handle()
    {
        /** @var Broadcaster $service */
        $service = app(Broadcaster::class);

        $model = $this->getModel();
        if (!$model) {
            return;
        }
        $relation = ModelRelations::getModelRelationByPath($model, $this->path);

        if ($relation instanceof Relation && $relation->getModel() instanceof BroadcastableObject) {
            $relation
                ->orderBy($relation->getModel()->getKeyName())
                ->with($this->with);

            foreach ($this->where as $where) {
                $relation->where($where[0], $where[1]);
            }

            $relation->chunk(500, function ($devices) use ($service) {
                $service->fireBatchObjectEvent($this->eventName, $devices, $this->eventAt);
            });
        } else {
            \Log::debug('Can not send batch of events');
        }
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
