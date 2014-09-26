Slagger
============

A [Slim PHP](http://www.slimframework.com/) middleware for generating swagger json for use with Swagger UI.
This middleware will automatically add swagger json endpoints for all your Swagger doc annotated classes. 
Uses [Zircote Swagger-php](https://github.com/zircote/swagger-php) to parse annotations.

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

\\ ... your app code

$app->run();
```

### Slagger(docsuri, scandir, options)
The constructor takes up to three arguments. 

####docsuri
The uri in your app that will return swagger json.

####scandir
The directory to scan for files with Swagger annotations. 

####options [optional]
Options passed through to the **getResource** function of the [Zircote swagger-php](https://github.com/zircote/Swagger-php) library.


### Annotations
The [Zircote Swagger-php](https://github.com/zircote/swagger-php) library will parse the Swagger annotations in your files.
See the [Swagger-php docs](http://zircote.com/swagger-php/) for what annotations are supported.

ex: 
```php 
/**
 * @SWG\Resource(
 *     apiVersion="0.1",
 *     swaggerVersion="1.2",
 *     resourcePath="/journey",
 *     basePath="http://myapi.com/api/v1"
 * )
 */
class Journey extends CRUD {
	// ...
}
```


