# Change Log
All notable changes to this project will be documented in this file.
See [keepachangelog.com](http://keepachangelog.com) for reference.

## [4.0.2] - 2020-10-27

### Fixed
- Fixed usage of stacktrace limits with parameter `error_handling.stacktrace_limit`. When used (set to other than `0`),
  no stack trace at all would be logged instead of the number of entries configured.

## [4.0.1] - 2020-09-22

### Fixed
- Fixed usage of route groups in `RouteLoader`. Since 4.0, routes were not correctly placed into groups.

## [4.0.0] - 2020-07-26

### Added
- The Starter Kit now includes a few new controllers for experimenting with session and cookie setting.
- Added DI parameter `error_handling.stacktrace_limit` - Limit the number of entries rendered in stacktrace logs.
  > PSR-15 middleware can cause excessive stacktraces. Use this to limit the number of entries in each stacktrace when logged.
  > Set to 0, which disables this feature by default.

### Changed
- PHP 7.3 or higher is now required.
- Slim 4.X is now required.
    - Please see the [4.0 Upgrade guide](./UPGRADE-4.0.md) for detailed instructions on required changes to your application.
- Symfony 5.X is now recommended.
- Added scalar typehints to various interfaces and classes.
- Updated possible cookie configuration for `CookieHandler`
    - Removed `expires` (Use `maxAge` instead).
    - Added `sameSite` (Defaults to `lax` when using included config files).
- Updated `CookieHandler`.
  > This class is now more PSR-7 compliant by only setting string values as headers.
  > Previously, it would set a Cookie class that would be further encoded by `EncryptedCookieMiddleware`.
  > Now, CookieHandler encrypts response cookies, and EncryptedCookieMiddleware handles decryption of request cookies.
- Removed `ExceptionHandler->attachSlim($slim)` and replaced with `ExceptionHandler->attachRequest($request)`
    - Slim itself is no longer needed to render errors, but the request is. Ensure you attach the request in your `index.php`.
- Environment variable `PANTHOR_ROUTES_DISABLE_CACHE_ON` has changed to `SLIM_ROUTING_IS_CACHE_DISABLED`.
- The DI parameter `routes.cached` is now `slim.routing.cache_file` (Set by `SLIM_ROUTING_CACHE_FILE` environment variable.

### Removed
- The included YAML files for symfony DI were removed.
    - `configuration/panthor.yml`
    - `configuration/panthor-slim.yml`
    - Use the PHP files at `config/panthor.php` and `config/slim.php` instead.
- `QL\Panthor\Bootstrap\CacheableRouter` was removed, and replaced with `CacheableRouteCollectorConfigurator`.
- Removed `QL\Panthor\Utility\ClosureFactory`.
- Removed `QL\Panthor\Utility\Stringify`.

## [3.4.1] - 2020-04-23

### Fixed
- `ErrorHandler` will now ignore errors when users silence errors with the "@" symbol.
    - This is used by several popular libraries such `predis`, `doctrine`, and `phpseclib` to ignore system errors
      while they manually convert them to exceptions.

## [3.4.0] - 2020-01-02

### Changed
- Twig v3.0 is now allowed (Twig v2.0 is still supported).
    - This affects classes:
        - `TwigTemplate`
        - `LazyTwig`

## [3.3.0] - 2018-07-01

### Changed
- Bumped minimum symfony versions to `~4.0` and minimum php version to `~7.1`
- The Error handler now outputs a default 500 response if an error occurs before Slim has launched.
    - Change this message with `$exceptionHandler->setFallbackError($body);`

### Added
- Add simpler way to bootstrap a panthor project: `composer create-project ql/mcp-panthor`.
    - This deprecates [quickenloans-mcp/panthor-skeleton](https://github.com/quickenloans-mcp/panthor-skeleton) which will no longer be updated.
    - See [starter-kit](starter-kit/) for example project layout.
- Added `config/panthor.php` and `config/slim.php` to provide Symfony DI config in **Fluent PHP format**.

## [3.2.1] - 2017-12-22

### Changed
- All services in `panthor-slim.yml` and `panthor.yml` are now public by default to enable Symfony 4 support.
- Bumped minimum symfony versions to `~3.3 || ~4.0`

## [3.2.0] - 2017-12-06

### Added
- **SessionInterface** and **JSONEncodedSession** for attaching Session to the request context.
  - Also added **SessionMiddleware** for loading session data from the cookie (Uses `CookieHandler`).

### Changed
- **LibsodiumSymmetricCrypto** (used for cookie encrption) is now compatible
  with `ext-sodium` and PHP 7.2.
- *[BC BREAK]* Changed **Di** utility class to **DI** and changed many methods and parameters.
  - Removed `symfony.debug` to control symfony DI caching
    > Use environment variable `PANTHOR_DI_DISABLE_CACHE_ON` instead.
  - Changed default config entrypoint from `configuration/config.yml` to `config/config.yaml`.
  - `buildDi($root, callable $modifier = null)` to `Di::buildDI($root, $resolveEnv = false)`
  - `Di:getDi($root, $class, callable $modifier = null)` to `getDI($root, array $options)`
  - `Di::dumpDi(ContainerBuilder $container, $class, $baseClass = null)` to `Di::cacheDI(ContainerBuilder $container, array $options)`
  - The only option generally needed is `class` - the fully qualified class name of your cached container.

### Removed
- Removed **BetterCachingFilesystem** from twig add-ons.
  > This replacement for Twig Filesystem was used to allow twig caches to be
  > generated on a build server, which may have a different absolute path (which
  > twig previously used for the cache key). Use `twig/twig >= ~1.27`.
- Removed `@root` synthetic path to application root in the DI container.
  > Set environment variable `PANTHOR_APPROOT` instead. We **highly recommend**
  > using `symfony/dotenv` for managing environment config.

## [3.1.0] - 2017-02-20

### Added

- Add `QL\Panthor\Bootstrap\GlobalMiddlewareLoader`
    - This loader can be used to easily add global middleware to Slim.
    - Example Usage:

    >
    > ```yml
    > # configuration/di.yml
    > parameters:
    >    global.middleware:
    >        - 'middleware1.service_name'
    >        - 'middleware2.service_name'
    > services:
    >     global_middleware_loader:
    >         class: 'QL\Panthor\Bootstrap\Setup\GlobalMiddlewareLoader'
    >         arguments: ['@service_container', '%global.middleware%']
    > ```
    >
    > ```php
    > # public/index.php
    >
    > $container
    >     ->get('global_middleware_loader')
    >     ->attach($app);
    > ```

### Changed

- The default `path` in `cookie.settings` is now `/` (previously it was blank).
    - This avoids a chrome bug which uses the current url page as the cookie
      path if it is not provided in the http response.

## [3.0.4] - 2016-11-17

### Changed

- Fixed potential bug in `QL\Panthor\Twig\Context` when using deeply nested and self-referencing arrays. (#16)

## [3.0.3] - 2016-07-21

### Changed

- Fixed broken `absoluteURIFor` method in URI Utility.
- Fix URI test filename so test will be executed.

## [3.0.2] - 2016-05-12

### Changed

- When specifying multiple middleware, they are now executed in the correct order.

  > Example route:
  > ```yaml
  > test_route:
  >     route: '/page'
        stack: ['mw.1', 'mw.2', 'mw.3', 'page']
  > ```
  >
  > Previously this would execute in the following order:
  > ```
  > mw.3 -> mw.2 -> mw.1 -> page -> mw.1 -> mw.2 -> mw.3
  > ```
  >
  > As of 3.0.2 middleware will run correctly in this order:
  > ```
  > mw.1 -> mw.2 -> mw.3 -> page -> mw.3 -> mw.2 -> mw.1
  > ```

## [3.0.1] - 2016-05-11

### Changed

- Require Slim 3.4
- Update **CacheableRouter** to avoid error caused by Slim 3.4 update.

## [3.0.0] - 2016-05-02

### Changed
- Require Slim 3.3 (from 2.x).
- Require Symfony 3.0 (from 2.x).
- **Routing**
    - Add `QL\Bootstrap\RouteLoader`.
    - Remove `QL\Panthor\Slim\RouteLoaderHook`.
- **Twig**
    - `urlFor` function changed to `uriFor`.
    - Remove `currentRoute` function.
- **PSR-7**
    - `QL\Panthor\ControllerInterface` signature has changed has changed to be PSR-7 compatible.
    - `QL\Panthor\Middleware` signature has changed to be PSR-7 compatible.
- **Cookies**
    - Remove `QL\Panthor\Http\EncryptedCookies`
    - Add `QL\Panthor\Middleware\EncryptedCookiesMiddleware` for handling PSR-7 encrypted cookies.
        - Must be the first global middleware set on `Slim\App`.
    - Add `QL\Panthor\HTTP\CookieHandler` convenience getter/setter.
        - Only use if using Encrypted Cookies!
        - Provides simple interface for `getCookie`, `withCookie`, and `expireCookie`.
        - Use to set or get cookies from controllers or other middleware.
- **Error Handling**
    - Error handling has changed significantly as we no longer recommend using exceptions for **controlling flow**.
      Instead of throwing exceptions, render a specific response directly to the PSR-7 response in your controllers
      or middleware.
    - Removed exception handling from **ErrorHandler** and moved to separate **ExceptionHandler**.
    - **ErrorHandler** now requires **ExceptionHandler** in its constructor.
    - Attaching the error handler to the **shutdown handler** is no longer done by default and must be done by calling
     `registerShutdown` in addition to `register`.
    - The new exception handler can now handle **content negotiation** to render a specific content type
      depending on the media type accepted by the http client. See error handling docs for more information.
- Rename `QL\Panthor\Utility\Json` to `QL\Panthor\Utility\JSON`.
- Rename `QL\Panthor\Utility\Url` to `QL\Panthor\Utility\URI`.
    - Change `urlFor` to `uriFor`.
    - Change `absoluteUrlFor` to `absoluteURIFor` (method signature has also changed).
    - Remove `currentRoute` method.
    - Remove `redirectFor`.
    - Remove `redirectForURL`.
- DI configuration has been split into 2 files:
    - Make sure your `config.yml` imports both.
    - `/panthor-slim.yml` for core slim DI definitions.
    - `/panthor.yml`
- **Configuration**
    - Added `panthor-slim.yml` for slim DI configuration.
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

### Added
- Add `QL\Panthor\Bootstrap\CacheableRouter`
    - This router allows route caching for FastRoute routes used by Slim.
- Add `QL\Panthor\HTTPProblem\ProblemRendererTrait` for easily rendering Problems.
    - Use this trait to render problems on the response instead of throwing exceptions.

### Removed
- **Middleware**
    - Remove **RequestBodyMiddleware**.
        - Slim responses support this functionality directly from `getParsedBody`.
- **Testing**
    - Remove **TestLogger**
        - Use `QL\MCP\Common\Testing\MemoryLogger` instead.
    - Remove **TestResponse**
        - Use `Slim\Http\Response` instead, it supports `__toString` for easy assertions.
- **Templating**
    - Remove **AutoRenderingTemplate**.
- **Slim Add-ons**
    - Remove `QL\Panthor\Bootstrap\SlimConfigurator` for automatically loading Slim hooks.
    - Remove `QL\Panthor\Slim\Halt`.
    - Remove `QL\Panthor\Slim\NotFound`.
    - Remove `QL\Panthor\Slim\ProtectErrorHandlerMiddleware`.
        - Slim now handles errors better, and this is no longer necessary.
- **Exceptions**
    - Remove **HTTPProblemException**.
    - Remove **NotFoundException**.
    - Remove **RequestException**.

## [2.4.0] - 2016-03-24

### Changed
- **Error Handling**
    - **ErrorHandler** now supports PHP 7 and **throwable errors**.
    - Added **HandledExceptionsTrait** to typecheck for a handler's ability to handle an exception or PHP 7 **throwable**.
    - **Note:** ExceptionHandlerInterface has changed, if you wrote your own handler it must be updated.
        - See [ExceptionHandlerInterface.php](src/ErrorHandling/ExceptionHandlerInterface.php)
        - See [NotFoundHandler.php](src/ErrorHandling/ExceptionHandler/NotFoundHandler.php) for an example.

## [2.3.1] - 2016-01-12

### Changed
- DI Service `@panthor.error_handling.html_renderer.twig` now uses **TwigTemplate** instead of **LazyTwig**.
- In **BaseHandler**, errors are now logged before attempting to render a response.

### Added
- Add `QL\Panthor\Templating\TwigTemplate`
    - This is a non-lazy version of **LazyTwig**.
    - It should be used for twig rendering during error handling, as lazy loading is more error-prone.

## [2.3.0] - 2015-12-15

Please note: This release has backwards compatibility breaks to remove links to proprietary packages.

### Removed
- Remove `QL\Panthor\Slim\McpLoggerHook`
    - Please see **MCPLoggerHook** in `ql/panthor-plugins`.
- Remove **ApacheAuthorizationHook**
    - This includes removal of `@slim.hook.apacheAuthorization` service.
- **Error Handling**
    - Removed **APIExceptionConfigurator**.
    - Removed **ExceptionConfigurator**.
    - Removed **FatalErrorHandler**.
- **HTTP Problem**
    - Remove usage of `ql/http-problem`, replaced by simple implementation in `QL\Panthor\HTTPProblem` namespace.
- **Encryption**
    - Remove `QL\Panthor\CookieEncryption\AESCookieEncryption`.
    - Remove `QL\Panthor\CookieEncryption\TRPCookieEncryption`.
        - Please see **TRPCookieEncryption** in `ql/panthor-plugins`.

### Changed
- **Error Handling**
    - Errors and exceptions are now handled by a single handler - `QL\Panthor\ErrorHandling\ErrorHandler`.
      > This handler will turn errors into exceptions, which are routed through a list of **exception handlers**.
      > Errors not thrown can be optionally logged, and allow application execution to continue.
    - Exception handlers implement **ExceptionHandlerInterface**, and can determine whether they will handle
      an exception.
      > If not handled by any handler, the exception will be rethrown to be handled by default PHP mechanisms.
- **HTTP Problem**
    - `QL\Panthor\HTTPProblem\HTTPProblem` replaces `QL\HttpProblem\HttpProblem`.

### Added
- **Error Handling**
    - Added exception handlers:
        - **BaseHandler**
            - Should always be used, and attached last. This is the last defense from an unhandled exception, and all
              exceptions that reach this handler will be logged.
        - **GenericHandler**
        - **NullHandler**
        - **HTTPProblemHandler**
        - **RequestExceptionHandler**
    - Added exception renderers:
        - **HTMLRenderer**
            - Passes error context to twig template, by default `error.html.twig` in template directory.
        - **ProblemRenderer**
            - Renders exceptions as http-problem, by default as json.
    - Added **ProtectErrorHandlerMiddleware**
        - This middleware is attached to Slim, and resets the error handler, since Slim 2.x forces its own handler
          when run.
- **Crypto**
    - Added LibsodiumSymmetricCrypto for libsodium-based authenticated symmetric encryption.
    - This is used for cookie encryption, and is the only encryption protocol provided with this library.

## [2.2.0] - 2015-07-27

### Added
- Add `QL\Panthor\ErrorHandling\ErrorHandler`
    - Logs and renders errors to Slim.
- Add `QL\Panthor\ErrorHandling\FatalErrorHandler`
    - Converts Fatal Errors to Exceptions through *ShutdownHandler*.
- Add Exception Configurators
    - Exception configurators allow filtering of exceptions through different handlers, which can render
      exceptions differently.
    - Example: An application with an API may want to render exceptions thrown by API controllers differently
      than exceptions thrown by html controllers.
    - See `QL\Panthor\ErrorHandling\APIExceptionConfigurator`
    - See `QL\Panthor\ErrorHandling\ExceptionConfigurator`
- Logging
    - Add `QL\Panthor\Slim\McpLoggerHook` for adding request parameters to logger defaults.

## [2.1.0] - 2015-06-29

### Added
- **TRPCookieEncryption** added for libsodium-based cookie encryption (preferred).
- **AESCookieEncryption** added for mcrypt-based cookie encryption.
    - This is the default, for backwards compatibility.

### Changed
- Encrypted Cookies
    - Encrypted Cookies now requires **mcp-crypto** `2.*`.
    - Cookies are now automatically json encoded/decoded.
    - DI: `%encryption.secret%` changed to `%cookie.encryption.secret%`
    - DI: Added `%cookie.unencrypted%` to allow a whitelist of cookie names that will not be encrypted.
- DI Configuration moved from `configuration/di.yml` to `configuration/panthor.yml`

## [2.0.4] - 2015-06-02

### Changed
- Restrict **mcp-core** to `1.*`.
- Restrict **mcp-crypto** to `1.*`.
- Remove `QL\Panthor\Bootstrap\RouteLoaderHook` that was intended to be removed in 2.0.
    - Please see `QL\Panthor\Slim\RouteLoaderHook` instead.

### Added
- **RouteLoaderHook** can now load routes from multiple sources.
    - Added `RouteLoaderHook::addRoutes(array $routes)` which can be called multiple times.

## [2.0.3] - 2015-03-24

### Changed
- **symfony/dependency-injection** >= `2.6.3` now required.
- DI: Factory services have been updated to use new `factory` syntax.

## [2.0.2] - 2015-02-12

### Fixed
- `QL\Panthor\Twig\BetterCachingFilesystem` now correctly resolves relative template file paths.

## [2.0.1] - 2015-01-20
- A callable can be passed to `QL\Panthor\Bootstrap\Di::buildDi` when building the container to modify services or
  parameters before compilation.
    - This can be used to inject parameters from the environment `_SERVER`.

## [2.0.0] - 2015-01-12

### Added
- DI: **slim.not.found** service added.
    - Similar to `slim.halt`, a convenience wrapper for `Slim::notFound` is provided by `slim.not.found`.

### Removed
- The Route Loader no longer injects the following di services.
    - `slim.environment`
    - `slim.request`
    - `slim.response`
    - `slim.parameters`
    - `slim.halt`

### Changed
- DI: **slim.halt** service is no longer set as a callable closure.
    - It will now be an instance of `QL\Panthor\Slim\Halt`, but it is still callable. If you typehinted to callable,
      no changes need to be made.
- DI: **slim.parameters** is replaced by **slim.route.parameters**.

## [1.1.0] - 2014-11-03

### Added
- **Dependency Injection**
    - Add **DI** convenience class for bootstrapping and caching DI container during build process.
    - Add included DI configuration to simplify app configuration.
        - Usage: include the following in application `config.yml`:
          ```resource: ../vendor/ql/panthor/configuration/di.yml```
- **Middleware and Hooks**
    - Add **ApacheAuthorizationHeaderHook** to restore lost Authorization header in default apache installations.
    - Add **RequestBodyMiddleware** to automatically decode form, json or multipart POST body requests.
        - This allows applications to simulatenously support multiple media types for POST data.
- **HTTP**
    - Add **EncryptedCookies** to encrypt cookies through QL Crypto library.
- **Utilities**
    - Add **Json** utility to wrap json encoding and decoding in OOP.
    - Add **Stringify** utilityto assist in building complex scalars for usage in symfony configuration.
    - Add **Url** utility to handle slim-related URL functions such as building URLs and redirecting.
- **Templating**
    - Add `QL\Panthor\TemplateInterface` to abstract template implementations from Controllers.
    - Add **BetterCachingFileSystem**.
        - This replacement for the default twig filesystem caches based on template file path
          **relative to application root**. This allows cached templates to be generated on a build server,
          separate from the application web server.
    - Add **Context** to allow adding of context incrementally over the life of a request, through middleware
      or other means.
    - Add **LazyTwig** which defers loading of template files until `render` is called.
    - Add **TwigExtension** for date and url convenience functions.
- **Testing**
    - Add **TestLogger** for an in-memory PSR-3 logger usable by tests.
    - Add **TestResponse** to stringify slim responses, useful for test fixtures.
    - Add **Spy** to add spy functionality to Mockery.
    - Add **MockeryAssistantTrait** for Spy convenience methods.

### Changed
- **RouteLoaderHook** has been moved to `QL\Panthor\Slim\RouteLoaderHook`
    - The existing class is deprecated and will be removed in the next major version.

## [1.0.1] - 2014-10-29

### Removed
- The sample application has been removed.
    - See `ql/panthor-app` or `ql/panthor-api` for example skeletons.

## [1.0.0] - 2014-06-20

Initial release of Panthor.

Panthor is a thin microframework built on top of Slim and Symfony components. It combines Slim with
Symfony DI and Twig. Routes and page middleware and organized into "stacks" and defined by yaml configuration
within Symfony DI configuration.

It is designed to be equally usable for full html web applications, RESTful APIs, or applications that combine both.
