# Changelog

[//]: # (https://keepachangelog.com/ru/0.3.0/)


## 1.0.1 - 2025-05-07

### Added
- Добавлен отчет об отправке уведомлений RuStoreReport в поджигаемых событиях
- Added report on sending notifications RuStoreReport in fired events
  - NotificationSent (```$report = $event->response;```)
  - NotificationFailed (```$report = Arr::get($event->data, 'report');```)

### Changed
- Изменена обработка ответов от сервера: все неуспешные ответы (не 2**) интерпретируются как ошибка отправки (включая 1** и 3**)
- Changed handling of server responses: all unsuccessful responses (not 2**) are interpreted as a sending error (including 1** and 3**)
- Дополнено описание пакета [Readme](README.md)
- The package description has been supplemented [Readme](README.md)

### Fixed
- Исправлено поджигание события NotificationSent при отсутствии успешно отправленных сообщений
- Fixed firing of NotificationSent event when there were no successfully sent messages

[//]: # (### Deleted)



## 1.0.0 - 2025-05-06

- initial release
