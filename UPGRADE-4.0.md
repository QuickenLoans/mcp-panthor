## Panthor 4.0 Upgrade Guide

Panthor 4 is a large update from 3.0 and may require significant changes to your application.

Most of this is due to the change from Slim 3 to Slim 4. Slim is the backbone and foundation of Panthor,
and underwent significant changes to reach v 4.0. The most important being decoupling of all slim components,
as well as changes to the signature of Middleware for PSR-15 compatibility.

[PSR-15](http://www.php-fig.org/psr/psr-15/) is an HTTP request handler standard for the PHP community. It allows
applications to standardize around a common Middleware interface with the goal of sharing more libraries among
different frameworks and codebases.

However, updating your application to be PSR-15 compliant is significant work, as middleware no longer have the same
signature as controllers (Controllers are unchanged).

It is recommended you read the [Slim 4 Upgrade Guide](http://www.slimframework.com/docs/v4/start/upgrade.html) before
reading the rest of this one.

Confused on the exact process required to update? Check out the [Panthor Starter Kit](./starter-kit)
and compare the [diff between skeleton v3.4.1 and v4.0.0](https://github.com/quickenloans/panthor-skeleton/compare/3.4.1...4.0.0)

### Table of Contents
- [Dependencies](#dependencies)
- [Configuration](#configuration)
- [Routing](#routing)
- [PSR-15 middleware](#psr-15-middleware)
- [Error Handling](#error-handling)

### Dependencies

Panthor now requires Slim `~4.5`, Symfony `~5.0` and PHP `>=7.3`.

To update your composer dependencies, run the following command:
```bash
composer require \
    slim/slim ~4.5 \
    slim/psr7 ~1.0 \
    ql/mcp-panthor ~4.0 \
    symfony/config ~5.0 \
    symfony/dotenv ~5.0 \
    symfony/dependency-injection ~5.0 \
    symfony/yaml ~5.0 \
    dflydev/fig-cookies ~2.0
```

### Configuration

Previously, YAML files were included that would specify Symfony DI for Slim and Panthor components.

If you use the included configs, include them in your `config.yaml`:
```yaml
# Before
imports:
    - resource: ../vendor/ql/mcp-panthor/configuration/panthor-slim.yml
    - resource: ../vendor/ql/mcp-panthor/configuration/panthor.yml

# After
imports:
    - resource: ../vendor/ql/mcp-panthor/config/slim.php
    - resource: ../vendor/ql/mcp-panthor/config/panthor.php

```

In addition, here are the following changes to the configuration:

Changed parameters:
- `slim.default_request_headers` removed.
<!-- - Added `slim.settings.http_version` (1.1)
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
- Added `cookie.settings` (default cookie settings) -->

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

### Routing

Slim 4 has some changes to routing. The route definitions are not changed at all, but how routes can be cached
may need to be updated.

TBD

### PSR-15 middleware

Slim 4 uses PSR-15 middleware, which has a different signature. The main difference is that the middleware and controller "handler"
method are now different, and the response is no longer passed into middleware.

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

Panthor 4
```php
interface MiddlewareInterface
{
    /**
     * The primary action of this middleware.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
```

##### Example Middleware

```php
class TestMiddleware implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Run some code
        $a = 1234;

        // Modify the request before passing to next middleware
        $request = $request->withAttribute('a', $a);

        // MUST call the handler or return a response
        return $handler->handle($request);
    }
}
```

### Error Handling

TBD

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

