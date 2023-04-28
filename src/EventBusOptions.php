<?php

namespace GPX\EventBus;

class EventBusOptions
{
    public array $with = [];

    public array $where = [];

    public array $watchedAttributes = [];

    public array $watchedRelations = [];

    public array $watchedEvents = ['saved'];

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

    public function where($field, $value): static
    {
        $this->where[] = [$field, $value];

        return $this;
    }

    public function watchAttributes(array $attributes): static
    {
        $this->watchedAttributes = $attributes;

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
