<?php

namespace GPX\EventBus;

class EventBusOptions
{
    public array $with = [];

    public array $watchWhen = [];

    public array $watchedAttributes = [];

    public array $watchedRelations = [];

    public array $watchedEvents = ['saved', 'deleted'];

    /**
     * Start configuring model with the default options.
     */
    public static function defaults(): static
    {
        return new static();
    }

    public function with(array $with): static
    {
        $this->with = $with;

        return $this;
    }


    public function watchAttributes(array $attributes): static
    {
        $this->watchedAttributes = $attributes;

        return $this;
    }

    public function watchModelWhen(string $field, string $operator, string $value): static
    {
        $key = $field.$operator;
        $values = isset($this->watchWhen[$key]) ? $this->watchWhen[$key][2] : [];
        $this->watchWhen[$key] = [$field, $operator, array_unique(array_merge($values, [$value]))];

        return $this;
    }

    /**
     * @param  Relation[]  $attributes
     * @return $this
     */
    public function watchRelations(array $attributes): static
    {
        $this->watchedRelations = $attributes;

        return $this;
    }

    /**
     * Send only if those events fired
     */
    public function events(array $events): static
    {
        $this->watchedEvents = $events;

        return $this;
    }
}