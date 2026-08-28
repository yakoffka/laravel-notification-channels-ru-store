<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Exceptions;

use Exception;
use Throwable;

/**
 * Сообщение превышает максимальный объем, допустимый RuStore.
 */
class MessageTooLargeException extends Exception
{
    /**
     * @param int $actualBytes
     * @param int $maxBytes
     * @param Throwable|null $previous
     */
    public function __construct(
        private readonly int $actualBytes,
        private readonly int $maxBytes,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('RuStore message size is %d bytes, maximum allowed is %d bytes.', $actualBytes, $maxBytes),
            0,
            $previous,
        );
    }

    /**
     * @return int
     */
    public function actualBytes(): int
    {
        return $this->actualBytes;
    }

    /**
     * @return int
     */
    public function maxBytes(): int
    {
        return $this->maxBytes;
    }
}
