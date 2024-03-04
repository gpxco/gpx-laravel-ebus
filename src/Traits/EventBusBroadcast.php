<?php

namespace GPX\EventBus\Traits;

use GPX\EventBus\Broadcaster;
use GPX\EventBus\Contracts\BroadcastableObject;
use GPX\EventBus\EventBusOptions;
use GPX\EventBus\Helpers\ModelRelations;
use GPX\EventBus\Jobs\SendBatchOfEvents;
use GPX\EventBus\Jobs\SendEvent;
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
        $events->each(function ($observebleEventName) use ($observers, $options) {
            foreach ($observers as $observerClassName => $relation) {
                $observerClassName::$observebleEventName(function (Model $model) use ($observebleEventName, $relation, $options, $observerClassName) {
                    $dirty = $model->getDirty();

                    // don't check changed attributes if the model was deleted
                    if ($observebleEventName !== 'deleted' && ! array_intersect(array_keys($dirty), $relation['attributes'])) {
                        return;
                    }

                    $now = Carbon::now();
                    if ($observerClassName === static::class) {
                        if (!static::isWatchable($model, $options->watchWhen)) {
                            return;
                        }
                        if ($model instanceof BroadcastableObject) {
                            if ($model->getConnection()->transactionLevel() == 0) {
                                /** @var Broadcaster $broadcaster */
                                $broadcaster = app(Broadcaster::class);
                                $broadcaster->fireObjectEvent($observebleEventName, $model, $now);
                            } else {
                                SendEvent::dispatch(
                                    $observebleEventName,
                                    $now,
                                    $model->getKey(),
                                    $observerClassName
                                )->afterCommit();
                            }
                        }
                    } elseif ($relation['backpath']) {
                        if (!static::isWatchable($model, $relation['watchWhen'])) {
                            return;
                        }
                        SendBatchOfEvents::dispatch(
                            'saved',
                            $now,
                            $model->getKey(),
                            $observerClassName,
                            $relation['backpath'],
                            $options->with,
                            $options->watchWhen
                        )->afterCommit();
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

    public static function isWatchable($model, array $conditions): bool
    {
        if (! empty($conditions) &&
            array_sum(array_map(function ($item) use ($model) {
                $isDirty = $model->isDirty($item[0]);

                if ($isDirty) {
                    $isCreated = $model->isDirty($model->getKeyName());
                    $original = $model->getOriginal($item[0]);
                    $originalValue = $original instanceof \UnitEnum ? $original->value : $original;
                    $dirty = $model->{$item[0]};
                    $dirtyValue = $dirty instanceof \UnitEnum ? $dirty->value : $dirty;
                    if ($item[1] == '!=') {
                        return count(array_intersect([$originalValue, $dirtyValue], $item[2])) != ($isCreated ? 1 : 2);
                    } else {
                        return count(array_intersect([$originalValue, $dirtyValue], $item[2])) > 0;
                    }
                } else {
                    if ($item[1] == '!=') {
                        return !in_array($model->{$item[0]}, $item[2]);
                    } else {
                        return in_array($model->{$item[0]}, $item[2]);
                    }
                }

            }, $conditions)) != count($conditions)
        ) {
            return false;
        }

        return true;
    }

    abstract public static function getEventBusOptions(): EventBusOptions;
}
