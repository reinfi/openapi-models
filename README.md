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

Your configuration file should return an array with the following settings:

```php
return [
    'paths' => [__DIR__ . '/spec'], # array of path to check for openapi files
    'outputPath' => __DIR__ . '/output', # output directory
    'namespace' => 'Api', # namespace for generated classes, can be empty
    'clearOutputDirectory' => true, # to remove all files in output directory, default is false
    'dateTimeAsObject' => false, # date/date-time definition will be `string` otherwise `DateTimeInterface`.
    'dateTimeFormat' => 'Y-m-d H:i:s', # format for serialize of date time fields
];
```

### Generate

To generate your files just run `php vendor/bin/openapi-models generate`.

If you like to store your configuration somewhere else you need to provide the file name to the command.

`php vendor/bin/openapi-models generate --config spec/openapi-models.php`

### Validate

To validate your files, i.e. if the specification matches the generated files, you can run `php vendor/bin/openapi-models validate`.

Make sure you use the same configuration file as used for the generate command. 

## Namespace

You can specify a custom namespace for a schema or property using the `x-php-namespace` extension.
This allows you to extend the default namespace configuration.

```yml
components:
  schemas:
    Foo:
      x-php-namespace: Bar
      type: object
      required:
        - id
      properties:
        id:
          type: number
```

## Types

This library does not support `mixed` type from open-api specification. 
Therefore, you always have to set a type in your specification.

## Date or DateTime

The following schema has date/date-time properties.

```yml
components:
  schemas:
    Test1:
      type: object
      required:
        - date
      properties:
        date:
          type: string
          format: date
        dateTime:
          type: string
          format: date-time
```

The default is to generate the fields as `string`-type because this would not require
any logic for serialization of the class. 

You can change the configuration `dateTimeAsObject` to `true` and then these fields will be of type `DateTimeInterface`. 

A serialization function is added to these classes to support native `json_encode`. If you do not use native json_encode you 
may need to provide an own implementation to fulfill open api specifications. 

The default format for a `date` is `Y-m-d`, for a `date-time`-type is `Y-m-d\TH:i:sP` (RFC3339), but you can configure it.

```php
'dateFormat' => 'Y-m-d',
'dateTimeFormat' => 'Y-m-d H:i:s',
```

This is just for convenience if you do not want to deal with time zones or just need different formats. 
