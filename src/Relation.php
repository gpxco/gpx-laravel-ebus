<?php

namespace GPX\EventBus;

class Relation
{
    protected array $watchWhen = [];

    public function __construct(protected string $attributePath, protected string $backPath)
    {
    }

    public static function d(string $attributePath, string $backPath): static
    {
        return new static($attributePath, $backPath);
    }

    public function watchModelWhen(string $field, string $operator, string $value): static
    {
        $key = $field.$operator;
        $values = isset($this->watchWhen[$key]) ? $this->watchWhen[$key][2] : [];
        $this->watchWhen[$key] = [$field, $operator, array_unique(array_merge($values, [$value]))];

        return $this;
    }

    public function getWatchWhen(): array
    {
        return $this->watchWhen;
    }

    public function getAttributePath(): string
    {
        return $this->attributePath;
    }

    public function getBackPath(): string
    {
        return $this->backPath;
    }
}
