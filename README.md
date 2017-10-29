# Carbon Framework [![Build Status](https://scrutinizer-ci.com/g/htmlburger/carbon-framework/badges/build.png?b=master)](https://scrutinizer-ci.com/g/htmlburger/carbon-framework/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/htmlburger/carbon-framework/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/htmlburger/carbon-framework/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/htmlburger/carbon-framework/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/htmlburger/carbon-framework/?branch=master)

Carbon Framework is a micro framework for WordPress which provides tools for M*VC and routing.

## Quickstart

1. `composer require htmlburger/carbon-fields:dev-master`
1. Make sure you've included the generated `autoload.php` file
1. Add the following to your functions.php:
    ```php
    add_action( 'init', function() {
        session_start(); // required for Flash and OldInput
    } );

    add_action( 'after_setup_theme', function() {
        \CarbonFramework\Framework::boot();

        Router::get( '/', function() {
            exit('Hello World!');
        } );
    } );
    ```

## Routing

### Route method

The method you call on the router when you start a route definitions defines which requestmethod the route will match

```php
Router::[get|post|put|patch|delete|options|any]( $target, $handler );
```

If you wish to match a specific set of methods you can also use the generic `Router::route()` method:

```php
Router::route( ['GET', 'HEAD', 'POST'], $target, $handler );
```

### Route conditions

#### URL

If you wish to match against a specific path:

```php
Route::get( '/foo/bar/', $handler );
```

If you wish to have parameters in the path:

```php
Route::get( '/foo/{param1}/bar/{param2?}/baz/{param3:\d+}/{param4?:\d+}', function( $request, $template, $param1, $param2, $param3, $param4 ) {
    // ...
} );
```

- `param1` - required, matches everything
- `param2` - optional, matches everything
- `param3` - required, matches a custom regex
- `param4` - optional, matches a custom regex

_Parameter values are passed as arguments to the handler method._

If you wish to add a rewrite rule for your route (if it does not match any predefined rewrite rule):

```php
Route::get( '/foo/bar/', $handler )
    ->rewrite( 'index.php' ); // see https://codex.wordpress.org/Rewrite_API/add_rewrite_rule
```

#### Custom

The custom condition allows you to add a callable which must return a boolean (whether the route has matched the current request or not):

```php
Route::get( ['custom', function() {
    $my_condition = true; // your custom code here
    return $my_condition;
}], $handler );
```

You can also pass parameters to use built-in callables, for example:

```php
Route::get( ['custom', 'is_tax', 'crb_custom_taxonomy'], $handler );
```

Any parameters you pass will be provided to both the callable AND the $handler:

```php
Route::get( ['custom', 'is_tax', 'crb_custom_taxonomy'], function( $request, $template, $taxonomy ) {
    // $taxonomy is passed after $request and $tempalte which are always passed to handlers
} );
```

This works with closures as well, which can be used to reduce duplication:

```php
Route::get( ['custom', function( $foo, $bar ) {
    // $foo and $bar are available here
    return true;
}, 'foo', 'bar'], function( $request, $template, $foo, $bar ) {
    // ... and here!
} );
// you may notice this use-case is a bit hard to read - exact same usage is not advisable
```

#### Multiple

The multiple condition allows you to specify an array of conditions which must ALL match:

```php
Route::get( ['multiple', [
    ['custom', 'is_tax', 'crb_custom_taxonomy'],
    ['custom', function() {
        return true;
    } ],
]], $handler );
```

The syntax can also be simplified by directly passing an array of conditions:

```php
Route::get( [
    ['custom', 'is_tax', 'crb_custom_taxonomy'],
    ['custom', function() {
        return true;
    } ],
], $handler );
```

#### Post ID

Matches against the current post id:

```php
Route::get( ['post_id', 10], $handler );
```

#### Post slug

Matches against the current post slug:

```php
Route::get( ['post_slug', 'about-us'], $handler );
```

#### Post template

Matches against the current post template:

```php
Route::get( ['post_template', 'templates/contact-us.php'], $handler );
```

#### Post type

Matches against the current post type:

```php
Route::get( ['post_type', 'crb_product'], $handler );
```

#### Has query var

Matches when a specified query var is present (any value is accepted):

```php
Route::get( ['has_query_var', 's'], $handler );
```

This is especially useful when dealing with custom endpoints ([add_rewrite_endpoint()](https://codex.wordpress.org/Rewrite_API/add_rewrite_endpoint)):

```php
add_action( 'init', function() {
    add_rewrite_endpoint( 'my_custom_endpoint', EP_PAGES ); // remember to refresh your rewrite rules!
} );

...

Route::get( ['has_query_var', 'my_custom_endpoint'], $handler );
```

When combined with the post template condition, you can create pages that optionally receive additional parameters in the url without using query arguments:

```php
add_action( 'init', function() {
    add_rewrite_endpoint( 'secret', EP_PAGES ); // remember to refresh your rewrite rules!
} );

...

Route::get( [
    ['post_template', 'templates/page-with-secret.php'],
    ['has_query_var', 'secret'],
], $handler );
```

#### Query var

Similar to the previous one, but this time match the query var to a specific value:

```php
Route::get( ['query_var', 'some_query_var_name', 'some_query_var_value'], $handler );
```

### Route groups

You can group URL-based routes into nested groups which will share the group url as a prefix:

```php
Route::group( '/foo/', function( $group ) {
    $group->get( '/bar/', $handler ); // will match '/foo/bar/'
    $group->get( '/baz/', $handler ); // will match '/foo/baz/'
} );
```

### Route handlers

A route handler can be any callable or a controller reference in the `CONTROLLER_CLASS@CONTROLLER_METHOD` format. For example:

```php
Router::get( '/', 'HomeController@index' );
```

... will create a new instance of the `HomeController` class and call it's `index` method.

If your controller class is registered in the IoC container with it's class name as the key, then the class will be resolved
from the container instead of directly being instantiated:

```php
$container = \CarbonFramework\Framework::getContainer();
$container[ HomeController::class ] = function() {
    // your custom instantiation code here, e.g.:
    return new HomeController();
}
```

Route handlers have a couple of requirements:

1. Must receive at least 2 arguments
    1. `$request` - an object representing the current request to the server
    1. `$template` - the template filepath WordPress is currently attempting to load
1. Must return an object implementing the `Psr\Http\Message\ResponseInterface` interface

Your controllers can extend the `\CarbonFramework\Controllers\Controller` abstract class which contains a number of utility methods for returning proper response objects - see the Controllers section below for more information.

### Route middleware

Middleware allow you to modify the request and/or response before it reaches the route handler. A middleware can be any callable or the class name of a class that implement `MiddlewareInterface` (see `src/Middleware/MiddlewareInterface`).

A common example for middleware usage is protecting certain routes to be accessible by logged in users only:

```php
class AuthenticationMiddleware implements \CarbonFramework\Middleware\MiddlewareInterface {
    public function handle( $request, Closure $next ) {
        if ( is_user_logged_in() ) {
            wp_safe_redirect( wp_login_url() );
            exit;
        }
        return $next( $request );
    }
}

Router::get( '/protected-url/')
    ->add( AuthenticationMiddleware::class );
```

You can also define global middleware which is applied to all defined routes when booting the framework:

```php
\CarbonFramework\Framework::boot( [
    'global_middleware' => [
        AuthenticationMiddleware::class
    ]
] );
```

## Controllers

TODO

```php
class MyController extends \CarbonFramework\Controllers\Controller {
    public function someHandlerMethod( $request, $template ) {
        return $this->output( 'Hello World!' );
        return $this->template( 'templates/about-us.php' );
        return $this->json( ['foo' => 'bar'] );
        return $this->redirect( home_url( '/' ) );
        return $this->error( 404 );
    }
}
```

## Flash

TODO

## OldInput

TODO

## Service Providers

TODO

## Templating

TODO
