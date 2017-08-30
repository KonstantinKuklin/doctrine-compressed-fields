Doctrine Compressed Fields
=
Doctrine Compressed Fields is a **library** which is allow to store data from different Entity properties in
one column by using bits mask.

[![Build Status](https://secure.travis-ci.org/KonstantinKuklin/doctrine-compressed-fields.png?branch=master)](https://travis-ci.org/KonstantinKuklin/doctrine-compressed-fields)
[![GitHub release](https://img.shields.io/github/release/KonstantinKuklin/doctrine-compressed-fields.svg)](https://github.com/KonstantinKuklin/doctrine-compressed-fields/releases/latest)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/KonstantinKuklin/doctrine-compressed-fields/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/KonstantinKuklin/doctrine-compressed-fields/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/KonstantinKuklin/doctrine-compressed-fields/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/KonstantinKuklin/doctrine-compressed-fields/?branch=master)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%207.0-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/packagist/l/KonstantinKuklin/doctrine-compressed-fields.svg)](https://packagist.org/packages/KonstantinKuklin/doctrine-compressed-fields)

DO NOT USE IN PRODUCTION! Developing still in progress.

Motivation 
------------

Sometimes we need to store simple boolean value like true or false and we use for it `tinyint(1)` which cost is 1 byte(bits).

`So we lose 7 bits` on every such value if a column! Just imagine we lose 7k bits(700b = 0.68kb) on each 1k rows with 1 bool
element stored in `tinyint(1)`.
Here you can find the solution, how to store data without losing memory and hdd free space.

Installation using composer
------------

Execute in console:
```bash
composer require konstantinkuklin/doctrine-compressed-fields
```

Documentation
-------------

* in progress

Running tests
-------------
To run the tests, you need the sqlite extension for php. On Unix-like systems, install:

```php5-sqlite```

On Windows, enable the extension by uncommenting the following lines in php.ini
```
extension = php_pdo_sqlite.dll
extension = php_sqlite3.dll
extension_dir = ext
```

Running the tests from the project root:

```bash
./vendor/bin/phpunit
```

On Windows run phpunit from the full path

```bash
phpunit
```
