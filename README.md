# OpenAPI Models generated for PHP

This library does not provide any client or server implementation 
for your open api definition. It just generates models defined in your
schemas. 

This library may not be feature complete for all openapi definition. If you miss a feature, just file an issue.

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

Default configuration file is `openapi-models.php`. 

To generate your files just run `php vendor/bin/openapi-models generate`.

Your configuration file should return an array with the following settings:

```php
return [
    'paths' => [__DIR__ . '/spec'], # array of path to check for openapi files
    'outputPath' => __DIR__ . '/output', # output directory
    'namespace' => 'Api', # namespace for generated classes, can be empty
    'clearOutputDirectory' => true, # to remove all files in output directory, default is false
];
```

If you like to store your configuration somewhere else you need to provide the file name to the command.

`php vendor/bin/openapi-models generate --config spec/openapi-models.php`
