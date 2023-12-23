# OpenAPI Models generated for PHP

This library does not provide any client or server implementation 
for your open api definition. It just generates models defined in your
schemas. 

## Why?
There are some common open api generators like [JanePHP](https://github.com/janephp/janephp) or [OpenAPI Generator](https://openapi-generator.tech) but they
create old PHP Syntax due to their backwards compatibility. 

- No readonly objects
- No constructor promoted properties
- No typed properties, except PHPDoc

That is the reason why this library exists to simply generate models
with new PHP syntax.

## Install

Use composer to install it as development dependency. 

`composer require --dev reinfi/openapi-models`

## Usage

`php vendor/bin/openapi-models generate`
