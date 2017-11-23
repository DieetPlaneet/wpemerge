# Carbon Framework [![Build Status](https://scrutinizer-ci.com/g/htmlburger/carbon-framework/badges/build.png?b=master)](https://scrutinizer-ci.com/g/htmlburger/carbon-framework/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/htmlburger/carbon-framework/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/htmlburger/carbon-framework/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/htmlburger/carbon-framework/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/htmlburger/carbon-framework/?branch=master)

Carbon Framework is a micro framework for WordPress which provides tools for M*VC and routing.

## Quickstart

1. `composer require htmlburger/carbon-framework:dev-master`
1. Make sure you've included the generated `autoload.php` file
1. Add the following to your functions.php:
    ```php
    add_action( 'init', function() {
        session_start(); // required for Flash and OldInput
    } );

    add_action( 'after_setup_theme', function() {
        \CarbonFramework\Framework::boot();

        Router::get( '/', function() {
            return cf_output( 'Hello World!' );
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

A route handler can be any callable or a reference in the `CONTROLLER_CLASS@CONTROLLER_METHOD` format. For example:

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

Refer to the Controllers section for more info on route handlers.

### Route middleware

Middleware allow you to modify the request and/or response before it reaches the route handler. A middleware can be any callable or the class name of a class that implement `MiddlewareInterface` (see `src/Middleware/MiddlewareInterface`).

A common example for middleware usage is protecting certain routes to be accessible by logged in users only:

```php
class AuthenticationMiddleware implements \CarbonFramework\Middleware\MiddlewareInterface {
    public function handle( $request, Closure $next ) {
        if ( ! is_user_logged_in() ) {
            return cf_redirect( wp_login_url() );
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

A controller can be any class and any method of that class can be used as a route handler.

Route handlers have a couple of requirements:

1. Must receive at least 2 arguments
    1. `$request` - an object representing the current request to the server
    1. `$template` - the template filepath WordPress is currently attempting to load
    1. You may have additional arguments depending on the route condition(s) you are using (e.g. URL parameters, custom condition arguments etc.)
1. Must return one the following:
    1. Any `string` which will be output literally
    1. Any `array` which will be output as a JSON response
    1. an object implementing the `Psr\Http\Message\ResponseInterface` interface.

To return a suitable response object you can use one of the built-in utility functions:

```php
class MyController {
    public function someHandlerMethod( $request, $template ) {
        return cf_template( 'templates/about-us.php' );
        return cf_redirect( home_url( '/' ) );
        return cf_error( 404 );
        return cf_response(); // a blank response object
        return cf_output( 'Hello World!' ); // same as returning a string
        return cf_json( ['foo' => 'bar'] ); // same as returning an array
    }
}
```

Since all of the above functions return an object implementing the `ResponseInterface` interface, you can use immutable chain calls to modify the response, e.g. changing the status:

```php
class MyController {
    public function someHandlerMethod( $request, $template ) {
        return cf_template( 'templates/about-us.php' )->withStatus( 201 );
    }
}
```

### cf_output( $output );

Returns a new response object with the supplied string as the body.

### cf_template( $templates, $context = [] );

Uses `locate_template( $templates )` to resolve a template and applies the template output as the response body.
Optionally, a context array can be supplied to be used from inside the template.

### cf_json( $data );

Returns a new response object json encoding the passed data as the body.

### cf_redirect( $url, $status = 302 );

Returns a new response object with location and status headers to redirect the user.

### cf_error( $status );

Returns a new response object with the supplied status code. Additionally, attempts to render a suitable `{$status}.php` template file.

### cf_response();

Returns a blank response object.

## Flash

TODO

## OldInput

TODO

## Service Providers

TODO

## Templating

Carbon Framework comes with a single template engine built-in - Php.
This template engine uses `extract()` for the template context and then includes the template file.
The resulting output is then passed as the rendered template string.

Implementing your own or a third-party engine is simple and straightforward - here's an example of how to use Twig:

1. `composer require twig/twig`
1. Create a new `TwigEngine.php` file
    ```php
    <?php

    use CarbonFramework\Templating\EngineInterface;

    class TwigEngine implements EngineInterface {
        protected $twig = null;

        public function __construct( $twig ) {
            $this->twig = $twig;
        }

        public function render( $file, $context ) {
            $template = $this->twig->load( substr( $file, strlen( ABSPATH ) ) );
            return $template->render( $context );
        }
    }
    ```
1. Replace the template engine used immediately after `\CarbonFramework\Framework::boot()` is called:
    ```php
    \CarbonFramework\Framework::boot();

    $container = \CarbonFramework\Framework::getContainer();
    $container['framework.templating.engine'] = function() {
        $loader = new Twig_Loader_Filesystem( ABSPATH );
        $twig = new Twig_Environment( $loader, array(
            'cache' => false, // you should add a cache - we're skipping it here for simplicity's sake
        ) );
        return new TwigEngine( $twig );
    };
    ```

With the above changes, templates rendered using `cf_template()` will now be processed using Twig instead of the default Php engine.
