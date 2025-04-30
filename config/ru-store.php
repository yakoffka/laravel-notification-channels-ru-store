<?php
declare(strict_types=1);

return [
    /*
     * ------------------------------------------------------------------------
     * Default RuStore project
     * ------------------------------------------------------------------------
     *
     * Для отправки push-уведомления используйте метод
     * POST https://vkpns.rustore.ru/v1/projects/$project_id/messages:send.
     *
     * Максимальный объем сообщения 4096 байт
     */
    'url' => 'https://vkpns.rustore.ru/v1/projects/%s/messages:send',

    'maximum_message_size_bytes' => 4096,

    'project_id' => env('RUSTORE_PROJECT_ID', 'none'),
    'token' => env('RUSTORE_TOKEN', 'none'),
];
