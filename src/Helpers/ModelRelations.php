<?php

namespace GPX\EventBus\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ModelRelations
{
    public static function getRelatedModelNameByPath(Model $model, string $attributePath): array
    {
        $relatedModelNames = explode('.', $attributePath);
        $relatedAttribute = array_pop($relatedModelNames);

        $relatedModel = $model;

        do {
            $relation = array_shift($relatedModelNames);

            $relatedModelName = static::getRelatedModelRelationName($relatedModel, $relation);

            if (! $relatedModelName) {
                \Log::error('Model "'.get_class($relatedModel).'" has no method "'.$relation.'"');

                return [];
            }

            $relationOrModel = $relatedModel->$relatedModelName();

            if ($relationOrModel instanceof Relation) {
                $relatedModel = $relationOrModel->getModel();
            } else {
                $relatedModel = $relationOrModel;
            }
        } while (! empty($relatedModelNames));

        return ['class' => get_class($relatedModel), 'attribute' => $relatedAttribute];
    }

    public static function getModelRelationByPath(Model $model, string $path): ?Relation
    {
        $relatedModelNames = explode('.', $path);
        $relatedAttribute = array_pop($relatedModelNames);

        $attributeName = [];
        $relatedModel = $model;

        do {
            $relation = array_shift($relatedModelNames);
            if ($relation) {
                $attributeName[] = $relatedModelName = static::getRelatedModelRelationName($relatedModel, $relation);
                $relatedModel = $relatedModel->$relatedModelName ?? $relatedModel->$relatedModelName();
            }
        } while (! empty($relatedModelNames));

        $attributeName[] = $relatedAttribute;

        $result = $relatedModel->$relatedAttribute();

        return $result instanceof Relation ? $result : null;
    }

    protected static function getRelatedModelRelationName(Model $model, string $relation): ?string
    {
        return Arr::first([
            $relation,
            Str::snake($relation),
            Str::camel($relation),
        ], function (string $method) use ($model): bool {
            return method_exists($model, $method);
        }, null);
    }
}
