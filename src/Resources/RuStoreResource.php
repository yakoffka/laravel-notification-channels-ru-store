<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Resources;

abstract class RuStoreResource
{
    /**
     * Правка ошибки phpstan: Unsafe usage of new static()
     */
    abstract public function __construct();

    /**
     * Map the resource to an array.
     *
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * @param ...$args
     * @return static
     */
    public static function create(...$args): static
    {
        return new static (...$args);
    }
}
