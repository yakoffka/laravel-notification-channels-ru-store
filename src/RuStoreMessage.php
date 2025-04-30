<?php
declare(strict_types=1);

namespace NotificationChannels\RuStore;

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
     * @param string|null $token Push-токен пользователя, полученный в приложении.
     * @param string|null $data Объект, содержащий пары "key": value.
     * @param MessageNotification|null $notification Базовый шаблон уведомления для использования на всех платформах.
     * @param MessageAndroid|null $android Специальные параметры Android для сообщений.
     */
    public function __construct(
        public ?string $token = null,
        public ?string $data = null,
        public ?MessageNotification $notification = null,
        public ?MessageAndroid $android = null,
    ) {
    }

    /**
     * Set the message token.
     *
     * @param string|null $token
     * @return $this
     */
    public function token(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Set the message data.
     *
     * @param array|null $data
     * @return $this
     */
    public function data(?array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Map the resource to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        // return array_filter([
        //     'token' => $this->token,
        //     'data' => $this->data,
        //     'notification' => $this->notification,
        //     'android' => $this->android,
        // ]);
        return array_filter(get_object_vars($this));
    }
}
