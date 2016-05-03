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
    - [Exception Handler](#exception-handler)
    - [Writing a Content Handler](#writing-a-content-handler)
- [Error Handling for APIs](#error-handling-for-apis)

## Background

Error handling in PHP sucks. "Errors" exhibit different behavior depending on their type.

See [QL\Panthor\ErrorHandling\ErrorHandler](../src/ErrorHandling/ErrorHandler.php)
See [QL\Panthor\ErrorHandling\ExceptionHandler](../src/ErrorHandling/ExceptionHandler.php)

#### Errors (`E_WARN`, `E_NOTICE`, etc)

Errors are thrown as `ErrorException` and optionally logged. Error levels to be thrown and logged can be
separately customized.

#### Super Fatals (`E_ERROR`, `E_PARSE`)

Super fatals are turned into `ErrorException` and sent directly to the exception handler.

#### Exceptions

Exception are handled by the main error handler, which forwards them to a stack of **Exception Handlers** that can be
attached to the ErrorHandler.

## Usage

**ErrorHandler** must be registered for both standard errors and shutdown (superfatals).
Additionally, Slim should be attached to **ExceptionHandler** so proper responses can be output through Slim.

The error handler has the following signature:
```php
namespace QL\Panthor\ErrorHandling;

use Exception;

class ErrorHandler
{
    public function __construct(ExceptionHandlerInterface $exceptionHandler, LoggerInterface $logger = null);

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
$handler->registerShutdown();
ini_set('display_errors', 0);

// Fetch slim
$app = $container->get('slim');

// Attach exception handler to Slim
$container
    ->get('exception.handler')
    ->attachSlim($app);

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

### Exception Handler

Throwables are routed through the exception handler. The exception handler then renders exceptions through
**Content Handlers**. There are a variety of content handlers provided with Panthor. These can be used to
ensure your errors are output with the correct content type (such as html, json, etc) and whether error
details should be hidden from the user.

A single content handler can be passed to the exception handler to always render errors the same way. But by default
**Negotiating Content Handler** is used, which delegates to other handlers depending on the `Accept` header in the request.

The default content handler list is as follows:

- [QL\Panthor\ErrorHandling\ContentHandler\HTMLTemplateContentHandler](../src/ErrorHandling/ContentHandler/HTMLTemplateContentHandler.php)

    > Handles `*/*` and `text/html` content types.
    >
    > By default this renders to the twig template at `$root/templates/error.html.twig`.

- [QL\Panthor\ErrorHandling\ContentHandler\HTTPProblemContentHandler](../src/ErrorHandling/ContentHandler/HTTPProblemContentHandler.php)

    > Handles `application/problem` content type.
    >
    > By default this renders as HTTP Problem JSON through **HTTPProblem JSONRenderer**.

- [QL\Panthor\ErrorHandling\ContentHandler\JSONContentHandler](../src/ErrorHandling/ContentHandler/JSONContentHandler.php)

    > Handles `application/json` content type.
    >
    > This renders errors as JSON.

- [QL\Panthor\ErrorHandling\ContentHandler\PlainTextContentHandler](../src/ErrorHandling/ContentHandler/PlainTextContentHandler.php)

    > Handles `text/plain` content type.
    >
    > This renders errors as plain text.

This list may be customized, or added to by changing DI configuration for the `@content_handler` service.

Other Handlers:
- [QL\Panthor\ErrorHandling\ContentHandler\NegotiatingContentHandler](../src/ErrorHandling/ContentHandler/NegotiatingContentHandler.php)
- [QL\Panthor\ErrorHandling\ContentHandler\LoggingContentHandler](../src/ErrorHandling/ContentHandler/LoggingContentHandler.php)

### Writing a Content Handler

If you have media types you would like to handle in specific ways, you can write your own handler and pass it to the **ExceptionHandler**.

Content Handlers must implement [ContentHandlerInterface](../src/ErrorHandling/ContentHandlerInterface.php):
```php
namespace QL\Panthor\ErrorHandling;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface ContentHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function handleNotFound(ServerRequestInterface $request, ResponseInterface $response);

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string[] $methods
     *
     * @return ResponseInterface
     */
    public function handleNotAllowed(ServerRequestInterface $request, ResponseInterface $response, array $methods);

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param Exception $exception
     *
     * @return ResponseInterface
     */
    public function handleException(ServerRequestInterface $request, ResponseInterface $response, Exception $exception);

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param Throwable $throwable
     *
     * @return ResponseInterface
     */
    public function handleThrowable(ServerRequestInterface $request, ResponseInterface $response, Throwable $throwable);
}
```

##### Example Content Handler:
```php
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ErrorHandling\ContentHandlerInterface;
use QL\Panthor\HTTP\NewBodyTrait;
use Throwable;

class XMLHandler implements ContentHandlerInterface
{
    use NewBodyTrait;

    /**
     * @inheritDoc
     */
    public function handleNotFound(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this
            ->withNewBody($response, '<message>Not Found</message>')
            ->withHeader('Content-Type', 'text/xml')
            ->withStatus(404);
    }

    /**
     * @inheritDoc
     */
    public function handleNotAllowed(ServerRequestInterface $request, ResponseInterface $response, array $methods)
    {
        return $this
            ->withNewBody($response, '<message>Method Not Allowed</message>')
            ->withHeader('Content-Type', 'text/xml')
            ->withStatus(405);
    }

    /**
     * @inheritDoc
     */
    public function handleException(ServerRequestInterface $request, ResponseInterface $response, Exception $exception)
    {
        return $this
            ->withNewBody($response, '<message>Internal Server Error</message>')
            ->withHeader('Content-Type', 'text/xml')
            ->withStatus(500);
    }

    /**
     * @inheritDoc
     */
    public function handleThrowable(ServerRequestInterface $request, ResponseInterface $response, Throwable $throwable)
    {
        return $this
            ->withNewBody($response, '<message>Internal Server Error</message>')
            ->withHeader('Content-Type', 'text/xml')
            ->withStatus(500);
    }
}
```

## Error Handling for APIs

If you want to ensure errors are always handled with the same content type, simply define the `@content_handler` service
to the handler of your choice.

In application `di.yml`:
```yaml
    # Change content handler to always output JSON
    content_handler:
        class: 'QL\Panthor\ErrorHandling\ContentHandler\JSONContentHandler'
        arguments: ['@json', '%slim.settings.display_errors%']
        calls:
            - ['setStacktraceLogging', ['%error_handling.log_stacktrace%']]
```
