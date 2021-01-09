## LARAVEL <-> SLIM Transceiver

This small helper allows you to run laravel on top of your existing Slim app.

### Install

```
composer require dreadkopp/laravel_slim_transceiver
```

### Usage

(i assume you already installed Laravel on top of your existing slim project)

##### Laravel-side:

add to the Bottom of your routes/web.php:

```
Route::any('{any}', [ \dreadkopp\LaravelSlimTransceiver\SlimTransceiver::class, 'handle'])
    ->withoutMiddleware( \App\Http\Middleware\VerifyCsrfToken::class)
    ->where('any', '.*');
```

##### Slim:

in /public you should have a slim.php, we need to slightly modify that.

First, rename it to sub_slim.php

Second we need a slight adjustment. Last line in your sub_slim.php should read something like

```
    $app->run();
```

change that to

```
   return $app->run(true);
```

Third you want to disable the start of session in your slim app code since the Transceiver will create a temporary one for you during runtime

##### Global

Last, point your webserver to public/index.php instead of public/slim.php


