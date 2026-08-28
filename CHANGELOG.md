# Changelog

[//]: # (https://keepachangelog.com/ru/0.3.0/)

## Unreleased

### Added

- Добавлена валидация полученных ru-store-push токенов перед отправкой запроса.
- Добавлена детализация исключений для ошибочных ответов RuStore: `InvalidArgumentException`, `NotFoundException`,
  `PermissionDeniedException`, `RuStoreInternalException`, `TooManyRequestsException`, `UnexpectedException`,
  `MessageTooLargeException`, `InvalidPushTokenException`.
- Добавлен `ResponseExceptionMapper`, преобразующий ошибочные HTTP-ответы RuStore в специализированные исключения.
- Добавлена локальная проверка максимального объема сообщения RuStore перед отправкой HTTP-запроса.
- Добавлена локальная проверка ru-store-push токенов: токен должен быть непустой строкой не больше 64 байт.
- Добавлен `RuStoreMessageValidator`, отвечающий за локальную валидацию ru-store-push токенов и размера сообщения.
- Класс `RuStoreReport` дополнен методом `target()`, возвращающим ru-store-push токен, для которого сформирован отчет.

### Changed

- **Breaking:** `RuStoreChannel::send()` и `RuStoreClient::send()` теперь возвращают коллекцию отчетов
  `RuStoreReport` по отдельным ru-store-push токенам, а не агрегирующий отчет `RuStoreReport`.
- **Breaking:** событие `NotificationSent` теперь содержит коллекцию успешных отчетов `RuStoreReport` по отдельным
  ru-store-push токенам в `$event->response`, а не агрегирующий объект `RuStoreReport`.
- **Breaking:** событие `NotificationFailed` теперь поджигается отдельно для каждой неуспешной отправки и содержит один
  отчет `RuStoreReport` по ru-store-push токену в `$event->data['report']`, а не агрегирующий объект `RuStoreReport`.
- Изменено поведение при пустом списке токенов пользователя: вместо выброса исключения
  `RuStorePushNotingSentException` отправка завершается пустой коллекцией в `$event->response` события
  `NotificationSent`.
- Изменено поведение при полностью неуспешной отправке: `NotificationSent` поджигается с пустой коллекцией успешных
  отчетов в `$event->response`, а ошибки передаются через события `NotificationFailed`.

### Fixed

### Deleted

- **Breaking:** удален прежний агрегирующий отчет `RuStoreReport`; имя `RuStoreReport` теперь используется для отчета
  по одному ru-store-push токену.
- **Breaking:** удалено общее исключение `RuStorePushException`.
- **Breaking:** удалено исключение `RuStorePushNotingSentException`; для внутренней ошибки RuStore используется
  `RuStoreInternalException`.

## [2.0.0] - 2026-08-26

### Added

- Добавлено Docker-окружение для разработки

### Changed

- Минимальная версия Laravel повышена до 11.0
- Поддержка Laravel 11, 12, 13
- Обновлены зависимости: `illuminate/notifications` и `illuminate/support` теперь поддерживают версии 11, 12, 13

### Fixed

### Deleted

- Прекращена поддержка Laravel 10.x и более ранних версий.

## [1.0.1] - 2025-05-07

### Added

- Добавлен отчет об отправке уведомлений RuStoreReport в поджигаемых событиях
- Added report on sending notifications RuStoreReport in fired events
    - NotificationSent (```$report = $event->response;```)
    - NotificationFailed (```$report = Arr::get($event->data, 'report');```)

### Changed

- Изменена обработка ответов от сервера: все неуспешные ответы (не 2**) интерпретируются как ошибка отправки (включая
  1** и 3**)
- Changed handling of server responses: all unsuccessful responses (not 2**) are interpreted as a sending error (
  including 1** and 3**)
- Дополнено описание пакета [Readme](README.md)
- The package description has been supplemented [Readme](README.md)

### Fixed

- Исправлено поджигание события NotificationSent при отсутствии успешно отправленных сообщений
- Fixed firing of NotificationSent event when there were no successfully sent messages

### Deleted

## [1.0.0] - 2025-05-06

- initial release
