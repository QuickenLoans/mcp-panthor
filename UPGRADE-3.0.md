## Panthor 3.0 Upgrade Guide

Panthor 3 is a large update from 2.0 and may require significant changes to your application.

Most of this is due to the change from Slim 2 to Slim 3. Slim is the backbone and foundation of Panthor,
and underwent massive changes to reach v 3.0. The most important being PSR-7 support.

[PSR-7](http://www.php-fig.org/psr/psr-7/) is an HTTP message standard for the PHP community and is one of the
most significant standards ever adopted by the PHP community. It allows applications to standardize around a common
HTTP interface with the goal of sharing more libraries among different frameworks and codebases.

However, updating your application to be PSR-7 compliant is significant work, as the interface of the HTTP **request**
and **response** is completely different. The good news is as this is unlikely to ever happen again.

It is recommended you read the [Slim 3 Upgrade Guide](http://www.slimframework.com/docs/start/upgrade.html) before
reading the rest of this one.

Confused on the exact process required to update? Check out the [Panthor Skeleton App]((https://github.com/quickenloans-mcp/panthor-skeleton)
and compare the [diff between skeleton v2.4.0 and v3.0.0](https://github.com/quickenloans-mcp/panthor-skeleton/compare/2.4.0...3.0.0)

### Table of Contents
- [Dependencies](#dependencies)
- [Configuration](#configuration)
- [Routing](#routing)
- [Twig and templating](#twig-and-templating)
- [PSR-7 and controllers/middleware](#psr-7-and-controllersmiddleware)
- [Cookies](#cookies)
- [Error Handling](#error-handling)
- [Utilities](#utilities)
- [Middleware](#middleware)
- [Testing](#testing)
- [Other](#other)

### Dependencies

Panthor now requires Slim `~3.3`, Symfony `~3.0` and PHP `~5.6 || ~7.0`.

To update your composer dependencies, run the following command:
```bash
composer require \
    slim/slim ~3.3 \
    ql/mcp-panthor ~3.0 \
    symfony/config ~3.0 \
    symfony/dependency-injection ~3.0 \
    symfony/yaml ~3.0
```

### Configuration

In included DI configuration has been split into two files:
- `panthor-slim.yml` (Standard definitions for slim components)
- `panthor.yml` (DI Definitions for panthor components and add-ons)

If you use the included configs, include them in your `config.yaml`:
```yaml
imports:
    - resource: ../vendor/ql/mcp-panthor/configuration/panthor-slim.yml
    - resource: ../vendor/ql/mcp-panthor/configuration/panthor.yml
```

In addition, here are the following changes to the configuration:

Changed parameters:
- `slim.hooks` removed.
- Added `slim.settings.http_version` (1.1)
- Added `slim.settings.chunk_size` (4096)
- Added `slim.settings.buffering` (append)
- Added `slim.settings.determine_route_before_mw` (false)
- Added `slim.settings.display_errors` (true)
- Added `slim.default_request_headers` (`text/html`)
- Added `slim.default_status_code` (200)
- Added `routes.cached` (`configuration/routes.cached.php`)
    - Relative file path to cached routes
- Added `routes.cache_disabled` (true)
- Added `symfony.debug` (true)
    - The DI utility now uses its own config parameter to determine whether to use cached container
- Added `cookie.delete_invalid` (true)
    - Should invalid cookies be deleted when they cannot be decrypted?
- Added `cookie.settings` (default cookie settings)

Changed Services:
- `@slim.configurator` removed
- `@slim.halt` removed
- `@slim.not.found` removed
- `@slim.cookies` removed
- `@slim.route` removed
- `@slim.environment` changed to `@environment`
- `@slim.request` changed to `@request` (Default request, do not inject into service constructors!)
- `@slim.response` changed to `@response` (Default response, do not inject into service constructors!)
- `@slim.router` changed to `@router`
- `@url` changed to `@uri`
- `@slim.hook.routes` changed to `@router.loader`
- Added `@cookie.handler`
- Added `@exception.handler`
- Added `@problem.renderer`
- Added `@content_handler` (for exception handler)

### Routing

With Slim v3 comes [FastRoute](https://github.com/nikic/FastRoute), a blazing fast router for applications. Unfortunately,
this has some significant changes to routing as URI paths and conditions must be specified differently.

#### Route parameters and conditions

Route conditions and parameters are now embedded in the uri path instead of separate properties.

Panthor 2 (with Slim routing)
```yaml
parameters:
    routes:
        api.endpoint.resource:
            method: 'POST'
            route: '/api/resource/:resource_id'
            stack: ['middleware.require_api_token', 'api.resource_controller']
            conditions:
                resource_id: '[0-9]+'
```

Panthor 3 (with FastRoute routing)
```yaml
parameters:
    routes:
        api.endpoint.resource:
            method: 'POST'
            route: '/api/resource/{resource_id:[0-9]+}'
            stack: ['middleware.require_api_token', 'api.resource_controller']
```

Note that instead of `:my_var`, we use `{my_var}` and the validation regex is embedded in the parameter token.

#### Groups

In addition, Slim v3 supports **groups**, to allow shared middleware or url paths. Simply nest `routes` under an existing
route and it will be used as a **group** instead of a **route**. See the following example:
```yaml
parameters:
    routes:
        hello_world:
            route: '/'
            stack: ['page.hello_world']

        my_group:
            route: '/my-group'
            stack: ['middleware.authorization']
            routes:
                goodbye_cruel_world:
                    route: '/goodbye_cruel_world/{name}'
                    stack: ['page.hello_world']

```

#### Route caching

FastRoute routes are cacheable, made possible by [QL\Panthor\Bootstrap\CacheableRouter](src/Bootstrap/CacheableRouter.php).

This router is used, but route caching is disabled by default. When deploying you'll want to set the following DI configuration:
```yaml
parameters:
    routes.cache_disabled: false
```

And generate cached routes by running a script like so during your build process:

```php
$root = __DIR__;
if (!$container = @include $root . '/config/bootstrap.php') {
    echo "An error occured while attempting to cache routes.\n";
    exit(1);
};

$router = $container->get('router');
$router->setCaching($container->get('router.cache_file'), false);

$app = $container->get('slim');
$routes = $container->get('router.loader');
$routes($app);

$router->initializeDispatcher();
```

### Twig and templating

**AutoRenderingTemplate** has been removed. This template allowed the response to be set directly on the template, and it
would be automatically added to the response when the template renderered (allowing controllers to use only a template,
and not the response).

With [PSR-7](http://www.php-fig.org/psr/psr-7) controllers, this flow is no longer possible, and controllers must
manually set the rendered template on the response body.

Due to changes with the **URI Utility**, some functions were changed in the TwigExtension:
- Changed `urlFor` to `uriFor`.
- Removed `currentRoute`.

### PSR-7 and controllers/middleware

In Panthor 2, the HTTP **Request** and **Response** were passed in the constructor of controllers/middleware. This was
done so you could minimize the dependencies of your application code. You could not inject those components if your
controllers did not use them.

With PSR-7 compatible middleware and controllers, this is no longer a concern. Middleware and Controllers must **ALWAYS**
be aware of both the request and response.

**NEVER** inject the request and response through constructors, as these are only the *default* request/response, and
are likely changed when middleware/controllers run and modify them. Any class or component that depends on data from
the request and/or sets data on the response must get the request/response from middleware or controllers.

##### ControllerInterface

Panthor 2
```php
interface ControllerInterface
{
    /**
     * The primary action of this controller. Any return from this method is ignored.
     *
     * @return null
     */
    public function __invoke();
}
```

Panthor 3
```php
interface ControllerInterface
{
    /**
     * The primary action of this controller.
     *
     * Must return ResponseInterface.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response);
}
```

##### MiddlewareInterface

Panthor 2
```php
interface MiddlewareInterface
{
    /**
     * The primary action of this middleware. Any return from this method is ignored.
     *
     * @return null
     */
    public function __invoke();
}
```

Panthor 3
```php
interface MiddlewareInterface
{
    /**
     * The primary action of this middleware.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param MiddlewareInterface|callable $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next);
}
```

Controllers and Middleware **MUST** always return a response, which is then passed to the next item in the
responding middleware/controller stack.

##### Example Controller

```php
class TestController implements ControllerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        // Render a template
        $rendered = $this->template->render([
            'test_data' => 1234
        ]);

        // Write to the body, and return the response
        $response->getBody()->write($rendered);
        return $response;
    }
}
```

##### Example Middleware

```php
class TestMiddleware implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // Run some code
        $a = 1234;

        // Modify the request before passing to next middleware
        $request = $request->withAttribute('a', $a);

        // MUST call the next middleware
        return $next($request, $response);
    }
}
```

#### Exceptions and controlling flow

A common practice is to have middleware do things like *validate the request* or *handle authentication and authorization*.
When a middleware failed, the recommended way to prevent the controller from being run is throw an exception from your
middleware such as the following:

```php
class MyMiddleware extends MiddlewareInterface
{
    /**
     * @throws Exception
     */
    public function __invoke()
    {
        if (!$userIsNotLoggedIn) {
            throw new Exception('Access Denied');
        }
    }
}
```

Part of the reason for this is to avoid adding dependencies. This middleware could function without using the **request**
or **response**. However, this follows the anti-pattern [using exceptions for control flow](http://c2.com/cgi/wiki?DontUseExceptionsForFlowControl)
and is discouraged.

Now with Slim 3 and PSR-7, you'll need to explicitly stop the middleware/controller chain. Since all middleware and controllers
are called with the request and response, there is no reason not to output directly to the response.

```php
class MyMiddleware extends MiddlewareInterface
{
    use NewBodyTrait;

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next);
    {
        if (!$userIsNotLoggedIn) {
            // Write to the response and return it, skip calling the next middleware.
            return $this
                ->withNewBody($response, 'Access Denied')
                ->withStatus(403);
        }

        // Otherwise, carry on and pass to the next middleware or controller.
        return $next($request, $response);
    }
}
```

A few utilities are provided to make this a bit easier and reduce boilerplate:
- [New Body Trait](src/HTTP/NewBodyTrait.php)
- [Problem Rendering Trait](src/HTTPProblem/ProblemRenderingTrait.php)

The previous example using **HTTP Problem**:
```php
class MyMiddleware extends MiddlewareInterface
{
    use ProblemRenderingTrait;

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next);
    {
        if (!$userIsNotLoggedIn) {
            $problem = new HTTPProblem(403, 'Access Denied');
            return $this->renderProblem($response, $this->renderer, $problem);
        }

        return $next($request, $response);
    }
}
```


### Cookies

Encrypted Cookies are now handled in a global middleware [EncryptedCookiesMiddleware](src/Middleware/EncryptedCookiesMiddleware.php)
using [dflydev/fig-cookies](https://github.com/dflydev/dflydev-fig-cookies). In addition, while in memory - Cookies
are stored in **OpaqueProperty** from [MCP Common](https://github.com/quickenloans-mcp/mcp-common). This ensures in
case the request or response are ever accidently output or logged that sensitive decrypted cookies are not passed in the clear.

To use encrypted cookies, attach the middleware to slim before running it in your `public/index.php`. Alternatively,
you can customize your DI definition for **Slim App**, calling `Slim\App::add($middleware)`. This middleware
will attempt to decrypt cookies before other middleware or controllers run, and encrypt them afterwards.

#### CookieHandler

Since we store these encrypted and decrypted values on the request and response, there is a lot of boilerplate to deal
with this. [CookieHandler](src/HTTP/CookieHandler.php) simplifies the interface for working with cookies. Please note
it should only be used if you also use **EncryptedCookiesMiddleware** - which is not enabled by default.

Example controller handling cookies with **CookieHandler**.
```php
class ExampleController extends ControllerInterface
{
    private $cookie;

    public function __construct(CookieHandler $cookie)
    {
        $this->cookie = $cookie;
    }

    public function __invoke($request, $response)
    {
        $testcookie = $this->cookie->getCookie($request, 'alphacookie');

        if ($testcookie === null) {
            // Cookie does not exist.
        } else {
            // Cookie exists

            // Expire cookie and save into response
            $response = $this->cookie->expireCookie($response, 'alphacookie');
        }

        // Set a new cookie, expires at default time (set in app config)
        $response = $this->cookie->withCookie($response, 'betacookie', '1234');

        // Set a new cookie, with custom expiry
        $response = $this->cookie->withCookie($response, 'gammacookie', 'abcd', '+7 days');

        return $response;
    }
 }
 ```

### Error Handling

Previously this was the recommended way to attach the error handler:
```php
// Error handling
$handler = $container->get('error.handler');
$handler->register();
ini_set('display_errors', 0);

// Application
$app = $container->get('slim');

// Attach Slim to error handler for error rendering
$handler->attach($app);

// Run Slim
$app->run();
```

This would register the Error Handler for **errors**, **exceptions**, and **superfatals** (shutdown). In addition, attaching
Slim to the error handler allowed the error handler to send responses through Slim to be rendered.

Registering the superfatal handler has been split to a separate method, and must now be done separately:
```php
// Error handling
$handler = $container->get('error.handler');
$handler->register();
$handler->registerShutdown();
ini_set('display_errors', 0);

// Application
$app = $container->get('slim');

// Attach Slim to exception handler for error rendering
$container
    ->get('exception.handler')
    ->attachSlim($app);

// Run Slim
$app->run();
```
In addition, Slim must now be attached to the **Exception Handler**, not the error handler.

Under the hood, the error handler would delegate to `ExceptionHandlers` for specially handling certain types of exceptions.

- `HTTPProblemHandler` (render **HTTP Problem**)
- `NotFoundHandler` (`NotFoundException`)
- `GenericHandler` (customizable)
- `RequestExceptionHandler` (RequestException from **RequestBodyMiddleware**)
- `BaseHandler` (`\Exception`, handler of last resort)

These handlers would set the correct status code, headers, and body. In the case of **HTTPProblemHandler**, it would ensure
all **HTTP Problem** exceptions were rendered as JSON. Users could create new exceptions and handlers so error
situations thrown by their application would render out correctly with the correct response to clients.

However, this follows the anti-pattern [using exceptions for control flow](http://c2.com/cgi/wiki?DontUseExceptionsForFlowControl)
and is a bad practice. Throwing exceptions aborts the current stack and can cause confusion in tracing how your
application logic flows and where errors and bugs originate.

With Slim 3 and PSR-7, we have a more clear and concise way of aborting the application - stopping execution in middleware
or controllers. See [Exceptions and controlling flow](#exceptions-and-controlling-flow) for more.

With the loss of custom handlers for exceptions, we need another way to ensure errors are rendered to clients with the
correct content type. This is where [Content Handlers](src/ErrorHandling/ContentHandlerInterface.php) comes in.

A Content Handler is attached to the exception handler to render **Not Found**, **Method Not Allowed**, and **Errors**.
If you want your app to always render JSON or another content type, great! Just define it as `content_handler` in DI.

The following Content Handlers are provided with Panthor:
- [HTMLTemplateContentHandler](src/ErrorHandling/ContentHandler/HTMLTemplateContentHandler.php) - Twig
- [HTTPProblemContentHandler](src/ErrorHandling/ContentHandler/HTTPProblemContentHandler.php) - HTTP Problem
- [JSONContentHandler](src/ErrorHandling/ContentHandler/JSONContentHandler.php) - JSON
- [LoggingContentHandler](src/ErrorHandling/ContentHandler/LoggingContentHandler.php) - Optionally log then pass through
- [NegotiatingContentHandler](src/ErrorHandling/ContentHandler/NegotiatingContentHandler.php) - Negotiate to a set of content handlers
- [PlainTextContentHandler](src/ErrorHandling/ContentHandler/PlainTextContentHandler.php) - Plain text

By default, the Panthor configuration uses the **NegotiatingContentHandler** with the following setup:

Media Type            | Handler
--------------------- | -------
`*/*`                 | **HTML Template** - through `templates/error.html.twig` error template.
`text/html`           | **HTML Template** - through `templates/error.html.twig` error template.
`application/problem` | **HTTP Problem** - as JSON
`application/json`    | **JSON**
`text/plain`          | **Plain Text**

**Please Note**: These handlers are NOT enabled by default.

To override the Slim default handlers to Panthor's handlers, add the following to your `di.yml` or DI configuration somewhere:
```yaml
services:
    # Replace slim handlers with our own
    notFoundHandler:     { alias: 'panthor.handler.notFoundHandler' }
    notAllowedHandler:   { alias: 'panthor.handler.notAllowedHandler' }
    phpErrorHandler:     { alias: 'panthor.handler.phpErrorHandler' }
    errorHandler:        { alias: 'panthor.handler.errorHandler' }
```

### Utilities

Panthor utilities `Json` and `Url` have been renamed to `JSON` and `URI` respectively. Please note the uppercase.

Previously, Slim handled redirects by throwing exceptions, but this is no longer recommended. So the **URI** utility
has lost the ability to redirect. Ensure you are not calling `URI::redirectFor` or `URI::redirectForURL`.

In addition the method `currentRoute` has been removed. Slim attaches the route to the request at the attribute `route`
so it can be retrieved directly from the request.

Please note the following method changes:
- `urlFor` is now `uriFor`
- `absoluteUrlFor` is now `absoluteURIFor` and it requires a **PSR-7 UriInterface** as the first parameter.

### Middleware

**RequestBodyMiddleware** has been removed (`QL\Panthor\Middleware\ProtectErrorHandlerMiddleware`).

Slim can now natively parse a variety of content types and add the parsed data to `getParsedBody` on the PSR-7 request.
This middleware is no longer necessary. Want to add additional media type parsers? No problem! This can be done either
as a middleware or by overwriting the DI definition for the default Slim request.

See [the slim user guide](http://www.slimframework.com/docs/objects/request.html#media-type-parsers) for more information.

**ProtectErrorHandlerMiddleware** has been removed (`QL\Panthor\Slim\ProtectErrorHandlerMiddleware`).

Slim previously set itself as the error handler, and this middleware was necessary to prevent that. Slim behaves better
with regards to error handling, so this middleware was removed.

### Testing

**TestResponse** has been removed.

For testing controllers it is useful to be able to render out a string representation of the HTTP response, without
using global PHP functions or `echo`. This was the purpose of **TestResponse**. The Slim response itself allows
this functionality, so a separate test stub is no longer necessary. Just use `Slim\Http\Response` in your tests.

**TestLogger** has been removed.

This logger stored messages in memory for later inspection for test assertions.`QL\MCP\Common\Testing\MemoryLogger`
provides the same functionality, so use that instead.

### Other

The following have been removed.

- `QL\Panthor\Templating\AutoRenderingTemplate`
- `QL\Panthor\Bootstrap\SlimConfigurator`
    - This configuration automatically attached **hooks** (global middleware) to Slim. Instead, manually do
      this in your `public/index.php` or in your DI configuration.
- `QL\Panthor\Slim\Halt`
    - This would throw a Slim exception and abort the application flow. Instead, manually set the response and
      stop execution in your middleware or controllers.
- `QL\Panthor\Slim\NotFound`
    - This would throw a Slim exception and abort the application flow. Instead, manually set the response and
      stop execution in your middleware or controllers.
- `QL\Panthor\Exception\HTTPProblemException`
    - HTTP Problems should be manually rendered onto the response instead of having the exception handler do it.
      See [ProblemRendereringTrait](src/HTTPProblem/ProblemRendereringTrait.php) for a convenience utility.
- `QL\Panthor\Exception\NotFoundException`
    - You can either throw `Slim\Exception\NotFoundException` or (preferred) return set 404 and stop the response
      in your middleware or controller.
- `QL\Panthor\Exception\RequestException`
