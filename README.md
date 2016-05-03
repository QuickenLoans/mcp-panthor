# MCP Panthor

[![Build Status](https://travis-ci.org/quickenloans-mcp/mcp-panthor.png)](https://travis-ci.org/quickenloans-mcp/mcp-panthor)
[![Code Climate](https://codeclimate.com/github/quickenloans-mcp/mcp-panthor/badges/gpa.svg)](https://codeclimate.com/github/quickenloans-mcp/mcp-panthor)
[![Latest Stable Version](https://poser.pugx.org/ql/mcp-panthor/version)](https://packagist.org/packages/ql/mcp-panthor)
[![License](https://poser.pugx.org/ql/mcp-panthor/license)](https://packagist.org/packages/ql/mcp-panthor)

A thin PHP microframework built on Slim and Symfony.

Slim + Symfony = :revolving_hearts:

:warning: Panthor was recently updated to 3.0. Check out the [Upgrade Guide for 3.0](UPGRADE-3.0.md).

Panthor uses the simplicity of Slim and provides a bit more structure for applications with additional Symfony
components. Utilities and helpers are provided to simplify template caching and dependency injection using Symfony
Dependency Injection and Slim. It can be used for html applications, APIs, or both.

- `Slim/Slim` - The core microframework.
- `Symfony/Config` - Cascading configuration to handle merging multiple config files.
- `Symfony/DependencyInjection` - A robust and flexible dependency injection container.

Here's a few of the features Panthor provides:

- Standard interfaces for Controllers, Middleware, and Templates
- Error Handling (with Content Negotiation)
- Cookie Encryption with PECL Libsodium
- All configuration through YAML (including Slim routes)
- Support for [HTTP Problem](https://tools.ietf.org/html/draft-ietf-appsawg-http-problem)
- Utilities for Unit Testing
- Utilities for Templating

## Table of Contents

- [Compatibility](#compatibility)
- [Starting a new application?](#starting-a-new-application)
    - [Installation](#installation)
    - [Quick Start](#quick-start)
- [Documentation](#documentation)
- [Dependencies](#dependencies)
    - [Optional Dependencies](#optional-dependencies)

## Compatibility

- **Panthor 1**
    - Slim ~2.0
    - Symfony ~2.0
- **Panthor 2**
    - Slim ~2.0
    - Symfony ~2.0
- **Panthor 3**
    - Slim ~3.3
    - Symfony ~3.0
    - PHP ~5.6 || ~7.0

## Starting a new application?

#### Installation

```
composer require ql/mcp-panthor ~3.0
```

See [Panthor Skeleton](https://github.com/quickenloans-mcp/panthor-skeleton) for an example skeleton.

Never used Composer, Slim or Symfony before? Here are some resources:
- [Composer - Getting Started](https://getcomposer.org/doc/00-intro.md)
- [Symfony Book - Service Container](http://symfony.com/doc/current/book/service_container.html)
- [Slim Framework v3 documentation](http://www.slimframework.com/docs)

#### Quick Start

1. Create an application with the following file hiearchy:

   > ```
   > configuration/
   >     bootstrap.php
   >     config.yml
   >     di.yml
   >     routes.yml
   > public/
   >     index.php
   > src/
   >     TestController.php
   > ```

2. Initialize composer with the following commands:

   > ```
   > composer init
   > composer require ql/mcp-panthor ~3.0 paragonie/random_compat ~1.1
   >
   > # Also require twig/twig if using html templating
   > composer require twig/twig ~1.20
   > ```
   >
   > Add autoloader configuration to `composer.json`:
   >
   > ```json
   > "autoload": {
   >     "psr-4": { "TestApplication\\": "src" }
   > }
   > ```

3. `configuration/config.yml` should import other config resources.

    > ```yaml
    > imports:
    >     - resource: ../vendor/ql/mcp-panthor/configuration/panthor-slim.yml
    >     - resource: ../vendor/ql/mcp-panthor/configuration/panthor.yml
    >     - resource: di.yml
    >     - resource: routes.yml
    > ```

4. `configuration/di.yml` will contain service definitions for your application, such as controllers.

    > ```yaml
    > services:
    >     page.hello_world:
    >         class: 'TestApplication\TestController'
    > ```

5. `configuration/routes.yml` contains routes.

    > Routes is simply another config parameter passed into the DI container. It maps a route name to a url and list of
    > services to call.
    > ```yaml
    > parameters:
    >     routes:
    >         hello_world:
    >             route: '/'
    >             stack: ['page.hello_world']
    > ```

6. `configuration/bootstrap.php` should load the composer autoloader and return the DI container.

    > ```php
    > <?php
    >
    > namespace TestApplication\Bootstrap;
    >
    > use QL\Panthor\Bootstrap\Di;
    > use TestApplication\CachedContainer;
    >
    > $root = __DIR__ . '/..';
    > require_once $root . '/vendor/autoload.php';
    >
    > return Di::getDi($root, CachedContainer::class);
    > ```

7. `public/index.php` loads the bootstrap, attaches routes and starts **Slim**.

    > ```php
    > <?php
    >
    > namespace TestApplication\Bootstrap;
    >
    > if (!$container = @include __DIR__ . '/../configuration/bootstrap.php') {
    >     http_response_code(500);
    >     echo "The application failed to start.\n";
    >     exit;
    > };
    >
    > $slim = $container->get('slim');
    > $routes = $container->get('router.loader');
    >
    > $routes($app);
    > $slim->run();
    > ```

8. `src/TestController.php` is a simple controller that can be **invoked** as a callable.

    > ```php
    > <?php
    >
    > namespace TestApplication;
    >
    > use Psr\Http\Message\ResponseInterface;
    > use Psr\Http\Message\ServerRequestInterface;
    > use QL\Panthor\ControllerInterface;
    >
    > class TestController implements ControllerInterface
    > {
    >     public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    >     {
    >         return $response->getBody()->write('Hello World!');
    >     }
    > }
    > ```

8. Don't forget your web server configuration!
   - Panthor is just Slim under the hood, so it uses the same NGINX or Apache configuration as Slim (standard
     `index.php` rewrite).
   - Check out the [slim documentation on web servers](http://www.slimframework.com/docs/start/web-servers.html)
     for more details.

Now just visit `localhost` (or your preferred virtual host name) and your controller should load!

This quickstart leaves out many things such as **Twig Templating**, **Cookie Encryption**, and **Error Handling**.
Check the documentation links below for further details.

## Documentation

- [Application Structure](docs/APPLICATION_STRUCTURE.md)
  > Details on where code and configuration goes.

- [How To Use](docs/USAGE.md)
  > Explanations of controllers and middleware, as well as services injected into the Di Container by Panthor.

- [Error Handling](docs/ERRORS.md)
  > How to use the included error handler and logger.

- [Web Server Configuration](docs/SERVER.md)
  > Example setups for nginx and apache.

## Dependencies

This library contains many convenience utilities and classes for your application. Some of this functionality requires
other libraries, but because they are optional, they are not strict requirements.

Library / Extension              | Used by
-------------------------------- | -----------
slim/slim                        | `Bootstrap\`
symfony/config                   | `Bootstrap\`
symfony/dependency-injection     | `Bootstrap\`
symfony/yaml                     | `Bootstrap\`
psr/log                          | `ErrorHandling\`
ql/mcp-common                    | `Encryption\`, `Twig\`, `HTTP\`, `Middleware\EncryptedCookiesMiddleware`
dflydev/fig-cookies              | `HTTP\CookieHandler`, `Middleware\EncryptedCookiesMiddleware`

### Optional Dependencies

Please take note of the following packages and include them in your composer `require` if you
use the associated Panthor functionality.

Library / Extension              | Required for
-------------------------------- | -----------
twig/twig                        | `Twig\`, `ErrorHandling\ContentHandler`
paragonie/random_compat or PHP7  | `Encryption\`
PECL Libsodium                   | `Encryption\`, `Middleware\EncryptedCookiesMiddleware`
