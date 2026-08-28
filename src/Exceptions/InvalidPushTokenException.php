<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Exceptions;

use Exception;
use Throwable;

/**
 * Некорректный ru-store-push токен.
 */
class InvalidPushTokenException extends Exception
{
    /**
     * @param string $reason
     * @param Throwable|null $previous
     */
    public function __construct(private readonly string $reason, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Invalid RuStore push token: %s', $reason), 0, $previous);
    }

    /**
     * @return string
     */
    public function reason(): string
    {
        return $this->reason;
    }
}
