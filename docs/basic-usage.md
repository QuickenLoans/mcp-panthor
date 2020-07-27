## Basic Usage

### Controllers and middleware

Routes must be given a "stack". This is a list of services retrieved from the service container that the framework
will call in order. The last entry in the stack is the controller. All other services are referred to as "middleware".

Slim controllers **MUST** return a Response and will be called with the following signature:
```php
$response = $controller($request, $response);
```

Slim middleware **MUST** return a Response and will be called with the following signature:
```php
$response = $middleware->process($request, $requestHandler);
```

Each middleware is responsible for modifying the request and/or response, *and calling the next middleware in the stack*
through the request handler.

The interfaces [QL\Panthor\ControllerInterface](../src/ControllerInterface.php) and
[QL\Panthor\MiddlewareInterface](../src/MiddlewareInterface.php) are provided for convenience that middleware and
controllers may implement, but no type checks are performed.

#### Dependency Injection Configuration

It is recommended applications import the panthor `panthor.php` and `slim.php` configuration files in their
application `config.yaml` file.

Example `config.yaml`:
```yaml
imports:
    - resource: ../vendor/ql/mcp-panthor/config/slim.php
    - resource: ../vendor/ql/mcp-panthor/config/panthor.php
    - resource: di.php
    - resource: routes.yaml
```

You may also copy these files to your application configuration and include that instead., as it may change
between releases. While Panthor makes every opportunity to follow [semver](http://semver.org/), the configuration may
not.

This configuration provides many boilerplates services. Check out the files directly to learn more:
[slim.php](../config/slim.php) and [panthor.php](../config/panthor.php).

Parameters                                | Description
----------------------------------------- | -----------
env(SLIM_DISPLAY_ERRORS)                  | `bool`: Should errors be shown on the page?
env(PANTHOR_APPROOT)                      | File path to the application root (location of your `composer.json`)
env(PANTHOR_DEBUG)                        | No built-in purpose. Use this for your application.
env(PANTHOR_DI_DISABLE_CACHE_ON)          | `bool`: Should DI container be auto-generated?
env(PANTHOR_TWIG_DEBUG)                   | `bool`: Should twig auto-generate cache files?
env(PANTHOR_ROUTES_DISABLE_CACHE_ON)      | `bool`: Should the Route cache be disabled? (For dev)
env(PANTHOR_TIMEZONE)                     | `America\Detroit`: The timezone to format dates
env(PANTHOR_COOKIE_SECRET)                | hex-encoded 128 characters used to encrypted cookies

Service                                   | Description
----------------------------------------- | -----------
slim                                      | Slim\App
environment                               | Slim\Environment
router                                    | QL\Panthor\CacheableRouter
---                                       | ---
QL\Panthor\Utility\URI                    | [URI](../src/Utility/URI.php) Utility
QL\Panthor\Utility\JSON                   | [JSON](../src/Utility/JSON.php) Utility
QL\MCP\Common\Clock                       | Clock from MCP Common
---                                       | ---
Psr\Log\LoggerInterface                   | PSR-3 Logger (NullLogger by default)
Twig\Environment                          | Twig Environment
QL\Panthor\Twig\Context                   | Global Twig Context
---                                       | ---
QL\Panthor\ErrorHandling\ErrorHandler     | [Error Handler](../src/ErrorHandling/ErrorHandler.php)
QL\Panthor\ErrorHandling\ExceptionHandler | [Exception Handler](../src/ErrorHandling/ExceptionHandler.php)
QL\Panthor\HTTP\CookieHandler             | [Cookie Handler](../src/HTTP/CookieHandler.php)
---                                       | ---
problem.renderer                          | HTTP Problem Renderer (JSON by default)
content_handler                           | Content Handler used by Exception Handler
