<?php
declare(strict_types=1);

namespace NotificationChannels\RuStore;

use JsonException;
use NotificationChannels\RuStore\Resources\MessageAndroid;
use NotificationChannels\RuStore\Resources\MessageNotification;

/**
 * Структура push-уведомления.
 */
class RuStoreMessage
{
    /**
     * Create a new message instance.
     *
     * @ param string|null $currentToken Push-токен пользователя, полученный в приложении.
     * @param string|null $data Объект, содержащий пары "key": value.
     * @param MessageNotification|null $notification Базовый шаблон уведомления для использования на всех платформах.
     * @param MessageAndroid|null $android Специальные параметры Android для сообщений.
     */
    public function __construct(
        //public ?string              $currentToken = null,
        public ?string              $data = null,
        public ?MessageNotification $notification = null,
        public ?MessageAndroid      $android = null,
    )
    {
    }

//    /**
//     * Set all tokens notifiable.
//     *
//     * @param array $tokens
//     * @return $this
//     */
//    public function setTokens(array $tokens): self
//    {
//        $this->reports = array_combine($tokens, array_fill(0, count($tokens), null));
//        dd($this->reports);
//
//        return $this;
//    }

    /**
     * Set the message token.
     *
     * @param string|null $token
     * @return $this
     */
    public function setCurrentToken(?string $token): self
    {
        $this->currentToken = $token;

        return $this;
    }

    /**
     * Set the message data.
     *
     * @param array|null $data
     * @return $this
     */
    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param string $token
     * @return string
     * @throws JsonException
     */
    public function getPayload(string $token): string
    {
        return json_encode(['message' => compact('token') + $this->toArray()], JSON_THROW_ON_ERROR);

    }

    /**
     * Map the resource to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter(get_object_vars($this));
    }
}
