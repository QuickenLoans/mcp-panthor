## Error Handling

- [Back to Documentation](README.md)
- [Application Structure](APPLICATION_STRUCTURE.md)
- [How To Use](USAGE.md)
- Error Handling
- [Cookies](COOKIES.md)
- [Web Server Configuration](SERVER.md)

### Table of Contents

- [Background](#background)
- [Usage](#usage)
    - [Error Logging](#error-logging)
    - [Customization](#customization)
    - [Exception Handlers](#exception-handlers)
    - [Writing an Exception Handler](#writing-an-exception-handler)
- [Error Handling for APIs](#error-handling-for-apis)

## Background

Error handling in PHP sucks. "Errors" exhibit different behavior depending on their type.

See [QL\Panthor\ErrorHandling\ErrorHandler](../src/ErrorHandling/ErrorHandler.php)

#### Errors (`E_WARN`, `E_NOTICE`, etc)

Errors are thrown as `ErrorException` and optionally logged. Error levels to be thrown and logged can be
separately customized.

#### Super Fatals (`E_ERROR`, `E_PARSE`)

Super fatals are turned into `ErrorException` and sent directly to the exception handler.

#### Exceptions

Exception are handled by the main error handler, which forwards them to a stack of **Exception Handlers** that can be
attached to the ErrorHandler.

## Usage

**ErrorHandler** must be registered, and then attached to **Slim** to take over handling of **Errors** and
**Not Founds**.

The error handler has the following signature:
```php
namespace QL\Panthor\ErrorHandling;

use Exception;

class ErrorHandler
{
    public function __construct(LoggerInterface $logger = null);

    public function handleException($throwable);
    public function handleError($errno, $errstr, $errfile, $errline, array $errcontext = []);
    public static function handleFatalError();
}
```
**NullLogger** will be used if no logger is provided, as errors are logged when handled. You may build this handler
yourself, or use the default service defined at `@error.handler` in the container.

Example `index.php`:
```php
// Code to get di container as $container

// Enable error handler first
$handler = $container->get('error.handler');
$handler->register();
ini_set('display_errors', 0);

// Fetch slim
$app = $container->get('slim');

// Attach error handler to Slim.
$handler->attach($app);

// Start app
$app->run();
```

### Error Logging

Errors that should be logged will be sent to the PSR-3 Logger defined at the service `@logger`. If you use Syslog,
Splunk, LogEntries or some other logging service, be sure to customize this service so errors are properly logged.

### Customization

`ErrorHandler::register(int $handledErrors = \E_ALL)`
> This method will register the handler for Errors, Exceptions, and Super Fatals (on shutdown).
> The type of errors to handle can be customized by the `$handledErrors` bitmask.
>
> Default value: `\E_ALL`

`ErrorHandler::setThrownErrors(int $thrownTypes)`
> Customize the types of errors that ErrorHandler will rethrown as `ErrorException`. For example this can be used to
> silence `E_STRICT` or `E_DEPRECATED` errors.
>
> Default value: `\E_ALL & ~\E_DEPRECATED & ~\E_USER_DEPRECATED`

`ErrorHandler::setLoggedErrors(int $loggedErrors)`
> Customize the types of errors logged to the PSR-3 Logger.
>
> Default value: `\E_ALL`

`ErrorHandler->handleNotFound()`
> Convenience method to throw `QL\Panthor\Exception\NotFoundException`

### Exception Handlers

Throwables are routed through a list of exception handlers that act as a stack. We use a stack of separate handlers
so different types of exceptions can be handled differently. For example, we may want to handle **NotFoundException**
differently from **HTTPProblemException** or `\ErrorException`.

The throwable will be sent to the handler that responds that it can handle the exception class type. If the handler
responds with `true`, **ErrorHandler** stops processing the stack and assumes the exception was handled.

Because of the way exceptions are processed through the stack, The **most specific handlers should be first
in the stack**. Always have a generic `\Throwable` handler last. This ensures the exception will always be handled.
Otherwise the exception will be an **unhandled exception** and cause a *white screen of death*.

The default handler stack for panthor is as follows:

- `QL\Panthor\ErrorHandling\ExceptionHandler\NotFoundHandler`

    > Handles **NotFoundException**.
    >
    > By default this renders to the twig template at `$root/templates/error.html.twig`.

- `QL\Panthor\ErrorHandling\ExceptionHandler\HTTPProblemHandler`

    > Handles **HTTPProblemException**.
    >
    > By default this renders as HTTP Problem JSON through **HTTPProblem JsonRenderer**.

- `QL\Panthor\ErrorHandling\ExceptionHandler\RequestExceptionHandler`

    > Handles **RequestException**.
    >
    > By default this renders errors from **RequestBodyMiddleware** as HTTP Problem JSON through **HTTPProblem JsonRenderer**.

- `QL\Panthor\ErrorHandling\ExceptionHandler\BaseHandler`

    > Handles all exceptions.
    >
    > As this is the last line of defense before an unhandled exception, all exceptions that reach here will be logged.
    > By default this renders to the twig template at `$root/templates/error.html.twig`.

This list may be customized, or added to by changing di configuration for the error handling services.

### Writing an Exception Handler

If you have exceptions you would like to handle in specific ways, you can write your own handler and attach it to the **ErrorHandler**.

Exception Handlers must implement [ExceptionHandlerInterface](../src/ErrorHandling/ExceptionHandlerInterface.php):
```php
namespace QL\Panthor\ErrorHandling;

use Exception;
use Throwable;

interface ExceptionHandlerInterface
{
    /**
     * Handle a throwable, and return whether it was handled and the remaining stack should be aborted.
     *
     * @param Exception|Throwable $throwable
     *
     * @return bool
     */
    public function handle($throwable);
}
```

[HandledExceptionsTrait](../src/ErrorHandling/ExceptionHandler/HandledExceptionsTrait.php) provides some convenience methods for configuring which throwables a handler should handle, and type checking against that list (including `\Throwable` and `\Exception` for both PHP 5.x and PHP 7 support).

##### Example Exception Handler:
```php
use QL\Panthor\ErrorHandling\ExceptionHandlerInterface;
use FooException;
use BarException;

class FoobarHandler implements ExceptionHandlerInterface
{
    use HandledExceptionsTrait;

    public function __construct()
    {
        $this->setHandledThrowables([
            FooException::class,
            BarException::class
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function handle($throwable)
    {
        if (!$this->canHandleThrowable($throwable)) return false;

        // render a custom error page here

        return true;
    }
}
```

## Error Handling for APIs

By default, errors are rendered to html templates and only as JSON if **HTTP Problem** is specifically used.
For a quick and dirty way to render all errors as json, **Change the `html_renderer` service** to render problems.

In application `di.yml`:
```yaml
    # Change html renderer to always do http-problem
    panthor.error_handling.html_renderer:
        parent: 'panthor.error_handling.problem_renderer'
```

If using this application in production, you should instead redefine the error handler service to make your error
handling configuration explicit and clear.
