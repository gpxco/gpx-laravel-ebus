<?php

namespace GPX\EventBus\Traits;

use GPX\EventBus\Broadcaster;
use GPX\EventBus\Contracts\BroadcastableObject;
use GPX\EventBus\EventBusOptions;
use GPX\EventBus\Helpers\ModelRelations;
use GPX\EventBus\Jobs\SendBatchOfEvents;
use GPX\EventBus\Relation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait EventBusBroadcast
{
    protected ?array $eventBusOptions = [];

    protected static function bootEventBusBroadcast(): void
    {
        $options = static::getEventBusOptions();

        $attributesModels = [];
        $model = new static();
        foreach ($options->watchedAttributes as $attribute) {
            if (! Str::contains($attribute, '.')) {
                $attributesModels += [$attribute => [
                    'class' => static::class,
                    'attribute' => $attribute,
                    'backpath' => null,
                    'watchWhen' => [],
                ]];
            }
        }
        foreach ($options->watchedRelations as $r) {
            if (! ($r instanceof Relation)) {
                continue;
            }

            if (Str::contains($r->getAttributePath(), '.')) {
                $relation = ModelRelations::getRelatedModelNameByPath($model, $r->getAttributePath());
                if (! $relation) {
                    \Log::error('The model "'.static::class.'" has no relation "'.$r->getAttributePath().'"');

                    continue;
                }
                $attributesModels += [$r->getAttributePath() => [
                    'class' => $relation['class'],
                    'attribute' => $relation['attribute'],
                    'backpath' => $r->getBackPath(),
                    'watchWhen' => $r->getWatchWhen(),
                ]];
            }
        }

        $observers = [];
        foreach ($attributesModels as $relation) {
            $className = $relation['class'];
            if (! isset($observers[$className])) {
                $observers[$className] = [
                    'attributes' => [],
                    'backpath' => $relation['backpath'],
                    'watchWhen' => $relation['watchWhen'],
                ];
            }
            $observers[$className]['attributes'][] = $relation['attribute'];
        }

        $events = collect($options->watchedEvents);
        $events->each(function ($eventName) use ($observers, $options) {
            foreach ($observers as $observerClassName => $relation) {
                $observerClassName::$eventName(function (Model $model) use ($eventName, $relation, $options) {
                    $dirty = $model->getDirty();
                    /** @var Broadcaster $broadcaster */
                    $broadcaster = app(Broadcaster::class);
                    if (! array_intersect(array_keys($dirty), $relation['attributes'])) {
                        return;
                    }

                    if (! empty($relation['watchWhen']) &&
                        array_sum(array_map(function ($item) use ($model) {
                            return $model->{$item[0]} == $item[2];
                        }, $relation['watchWhen'])) != count($relation['watchWhen'])
                    ) {
                        return;
                    }

                    $now = Carbon::now();
                    $className = get_class($model);

                    if ($className === static::class) {
                        if ($model instanceof BroadcastableObject) {
                            $broadcaster->fireObjectEvent($eventName, $model, $now);
                        }
                    } elseif ($relation['backpath']) {
                        SendBatchOfEvents::dispatch(
                            $eventName,
                            $now,
                            $model->getKey(),
                            $className,
                            $relation['backpath'],
                            $options->with,
                            $options->where
                        );
                    }
                });
            }
        });
    }

    public function getObjectName(): string
    {
        return strtolower(preg_replace('/^.*\\\\/', '', get_class($this)));
    }

    public function getEventKey(): string
    {
        return $this->getObjectName().':'.$this->getKey();
    }

    /**
     * Payload of event
     */
    public function toBroadcast(): array
    {
        return (array) $this->toArray();
    }

    abstract public static function getEventBusOptions(): EventBusOptions;
}
