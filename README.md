# Dependency free Apple Push Notifications for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/apantle/laravel-notification-apn-http2.svg?style=flat-square)](https://packagist.org/packages/apantle/laravel-notification-apn-http2)
[![Build Status](https://img.shields.io/travis/apantle/laravel-notification-apn-http2/master.svg?style=flat-square)](https://travis-ci.org/apantle/laravel-notification-apn-http2)
[![Total Downloads](https://img.shields.io/packagist/dt/apantle/laravel-notification-apn-http2.svg?style=flat-square)](https://packagist.org/packages/apantle/laravel-notification-apn-http2)

This package doesn't require 3rd party dependencies, and it allows
sending of pushes to multiple apps, using differente PEM
certificates and Apple Bundle Ids.

## Installation

You can install the package via composer:

```bash
composer require apantle/laravel-notification-apn-http2
```

## Usage

Store your certificates in directory `certificates`
(sibling to directory `app`)

Implement `toApn` method in your notification class:

```php
public function toApn($notifiable): ApnHttp2Message
{
    return ApnHttp2Message::create(
        $this->title,
        '',
        $this->message,
        [
            'customKey' => 'customData',
        ]
    )
      ->setTopic($notifiable->org->topic)
      ->setCertificateFile($notifiable->org->topic)
    ;
}
```

Implement method `routeNotificationForApn()` in your notifiable models, 
returning a single device token or an array of tokens:

```php
public function routeNotificationForApn($notification)
{
    return empty($notification->tokens)
        ? $this->devices->pluck('token')
        : $notification->tokens;
}
```

### Configuration

``` env
APN_HTTP2_PRODUCTION=true
APN_HTTP2_TOPIC=work.jefrancomix.demo
APN_HTTP2_CERTIFICATE=demo.pem
APN_HTTP2_CERT_PASSWORD=Aw3$om3!
```

## Credits

- [Jesus Franco](https://github.com/apantle)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
