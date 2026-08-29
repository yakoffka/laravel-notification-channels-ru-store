<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Services;

use NotificationChannels\RuStore\Exceptions\InvalidPushTokenException;
use NotificationChannels\RuStore\Exceptions\MessageTooLargeException;

class RuStoreMessageValidator
{
    /**
     * Максимальный объем сообщения 4096 байт
     */
    public const MAX_MESSAGE_BYTES = 4096;

    /**
     * Максимальная длина ru-store-push токена
     */
    public const MAX_PUSH_TOKEN_BYTES = 64;

    /**
     * Валидация ru-store-push токена
     *
     * @param mixed $token
     * @return string
     * @throws InvalidPushTokenException
     */
    public function validateToken(mixed $token): string
    {
        if ( ! is_string($token)) {
            throw new InvalidPushTokenException(sprintf('expected string, got %s', get_debug_type($token)));
        }

        if (trim($token) === '') {
            throw new InvalidPushTokenException('token is empty');
        }

        $bytes = mb_strlen($token, '8bit');

        if ($bytes > self::MAX_PUSH_TOKEN_BYTES) {
            throw new InvalidPushTokenException(sprintf(
                'token size is %d bytes, maximum allowed is %d bytes',
                $bytes,
                self::MAX_PUSH_TOKEN_BYTES,
            ));
        }

        return $token;
    }

    /**
     * @param string $payload
     * @return void
     * @throws MessageTooLargeException
     */
    public function ensureMessageFitsLimit(string $payload): void
    {
        $bytes = mb_strlen($payload, '8bit');

        if ($bytes > self::MAX_MESSAGE_BYTES) {
            throw new MessageTooLargeException($bytes, self::MAX_MESSAGE_BYTES);
        }
    }
}
