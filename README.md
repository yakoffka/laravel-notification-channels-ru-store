Please see [this repo](https://github.com/laravel-notification-channels/channels) for instructions on how to submit a channel proposal.

[//]: # (# A Boilerplate repo for contributions)

[//]: # ()
[//]: # ([![Latest Version on Packagist]&#40;https://img.shields.io/packagist/v/laravel-notification-channels/ru-store.svg?style=flat-square&#41;]&#40;https://packagist.org/packages/laravel-notification-channels/ru-store&#41;)

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

[//]: # ([![Build Status]&#40;https://img.shields.io/travis/laravel-notification-channels/ru-store/master.svg?style=flat-square&#41;]&#40;https://travis-ci.org/laravel-notification-channels/ru-store&#41;)

[//]: # ([![StyleCI]&#40;https://styleci.io/repos/:style_ci_id/shield&#41;]&#40;https://styleci.io/repos/:style_ci_id&#41;)

[//]: # ([![SensioLabsInsight]&#40;https://img.shields.io/sensiolabs/i/:sensio_labs_id.svg?style=flat-square&#41;]&#40;https://insight.sensiolabs.com/projects/:sensio_labs_id&#41;)

[//]: # ([![Quality Score]&#40;https://img.shields.io/scrutinizer/g/laravel-notification-channels/ru-store.svg?style=flat-square&#41;]&#40;https://scrutinizer-ci.com/g/laravel-notification-channels/ru-store&#41;)

[//]: # ([![Code Coverage]&#40;https://img.shields.io/scrutinizer/coverage/g/laravel-notification-channels/ru-store/master.svg?style=flat-square&#41;]&#40;https://scrutinizer-ci.com/g/laravel-notification-channels/ru-store/?branch=master&#41;)

[//]: # ([![Total Downloads]&#40;https://img.shields.io/packagist/dt/laravel-notification-channels/ru-store.svg?style=flat-square&#41;]&#40;https://packagist.org/packages/laravel-notification-channels/ru-store&#41;)

This package makes it easy to send notifications using [RuStore](link to service) with Laravel 10.x.


## Contents

- [Installation](#installation)
	- [Setting up the RuStore service](#setting-up-the-RuStore-service)
- [Usage](#usage)
	- [Available Message methods](#available-message-methods)
- [Changelog](#changelog)
- [Testing](#testing)
- [Security](#security)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)


## Installation

```bash
  composer require yakoffka/laravel-notification-channels-ru-store
```

### Setting up the RuStore service

Optionally include a few steps how users can set up the service.

## Usage

В классе User необходимо реализовать метод, возвращающий массив токенов уведомляемого пользователя:

```php
    /**
     * Получение массива ru-store пуш-токенов.
     * Используется пакетом laravel-notification-channels/rustore
     *
     * @return array
     */
    public function routeNotificationForRuStore(): array
    {
        return $this->ru_store_tokens;
    }
```


### Available Message methods

A list of all available options

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Security

If you discover any security related issues, please email yagithub@mail.ru instead of using the issue tracker.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [yakOffKa](https://github.com/yakoffka)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
