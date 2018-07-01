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
$response = $controller($request, $response, $nextMiddleware);
```

Each middleware is responsible for modifying the request and/or response, *and calling the next middleware in the stack*.

This would be a valid middleware:
```php
$middleware = function($request, $response, callable $next) {
    return $next($request, $response);
};
```

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

This configuration provides many boilerplates services.

Service                  | Description
------------------------ | -----------
env(PANTHOR_APPROOT)     | The application root. NO TRAILING SLASH.
slim                     | Slim\App
environment              | Slim\Environment
router                   | Slim\Router (or [CacheableRouter](../src/Bootstrap/CacheableRouter.php))
---                      | ---
uri                      | [URI](../src/Utility/URI.php) Utility
json                     | [JSON](../src/Utility/JSON.php) Utility
clock                    | Clock from MCP Common
---                      | ---
logger                   | PSR-3 Logger (NullLogger by default)
twig.environment         | Twig Environment
twig.template            | Base Twig Template
twig.context             | Global Twig Context
---                      | ---
error.handler            | Error Handler
exception.handler        | Exception Handler
problem.renderer         | HTTP Problem Renderer (JSON by default)
content_handler          | Content Handler used by Exception Handler
