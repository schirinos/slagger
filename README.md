Slagger
============

A Slim PHP middleware for generating swagger json documentation for use with Swagger UI.

## Introduction
This [Slim PHP](http://www.slimframework.com/) middleware will automatically add swagger json endpoints
for all your Swagger annotated classes. See the [Zircote Swagger-php](http://zircote.com/swagger-php/)
docs for what annotations are supported.

## Install Using Composer
```
{
    "require": {
        "scrumptious\slagger": "0.3.0"
    }
}
```

## Usage
```php

$app = new \Slim\Slim();

// Inject as Slim application middleware
$app->add(new \Slagger\Slagger('/api/v1/docs', __DIR__.'/../lib'));

\\ ... you app code

$app->run();
```

The constructor takes up to three arguments. 

### Slagger(docsuri, scandir, options)

####docsuri
The uri in your app that will return swagger json.

####scandir
The directory to scan for file with Swagger annotations. 

####options
Options passed through to the **getResource** function of the [Zircote swagger-php](https://github.com/zircote/Swagger-php) library.