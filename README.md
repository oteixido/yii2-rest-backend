# Yii2 REST backend ![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-blue.svg) [![Build Status](https://travis-ci.org/oteixido/yii2-rest-backend.svg?branch=master)](https://travis-ci.org/oteixido/yii2-rest-backend)

REST backend for Yii2 applications

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
