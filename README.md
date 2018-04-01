# Yii2 REST backend [![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.html) [![GitHub version](https://img.shields.io/badge/version-0.1-red.svg)] [![Build Status](https://travis-ci.org/oteixido/yii2-rest-backend.svg?branch=master)](https://travis-ci.org/oteixido/yii2-rest-backend)

REST backend for Yii2 applications.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
$ composer require oteixido/yii2-rest-backend "*"
```

or add

```
"oteixido/yii2-rest-backend": "*"
```

to the require section of your `composer.json` file.

## Configuration

To use this extension, simply add the following code in your application configuration:

```php
return [
    //....
    'components' => [
        'httpClient' => [
            'class' => '\oteixido\rest\http\HttpClient',
            'baseUrl' => 'https://localhost.localdomain/api/v1',
            'username' => 'username',    // Default no username
            'password' => 'password',    // Default no password
            'timeout' => 5,              // Default 10 seconds
            'sslVerify' => false,        // Default true
        ],
    ],
];
```

## Testing

Create docker image *yii2-rest-backend* for testing environment.

```bash
$ docker build . --tag yii2-rest-backend
$ docker run -it --rm -v "$PWD":/app -w /app yii2-rest-backend composer install
```
Execute tests.

```bash
$ docker run -it --rm -v "$PWD":/app -w /app yii2-rest-backend ./vendor/bin/codecept run
```

## License

[GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html)
