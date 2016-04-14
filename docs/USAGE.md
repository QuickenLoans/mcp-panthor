## Usage

- [Back to Documentation](README.md)
- [Application Structure](APPLICATION_STRUCTURE.md)
- How To Use
- [Error Handling](ERRORS.md)
- [Cookies](COOKIES.md)
- [Web Server Configuration](SERVER.md)

### Controllers and middleware

Routes must be given a "stack". This is a list of services retrieved from the service container that the framework
will call in order. The last entry in the stack is the controller. All other services are referred to as "middleware".

There are no restrictions for what a controller or middleware looks like, but they must be `callable`. Typically this
is achieved by implementing the magic method `__invoke` on the controller class. No arguments are passed to the
controller or middleware when they are called.

The interfaces `QL\Panthor\ControllerInterface` and `QL\Panthor\MiddlewareInterface` are provided for convenience that
middleware and controllers may implement, but no type checks are performed.


#### Dependency Injection Configuration

It is recommended applications import the panthor `panthor.yml` configuration file in their application `config.yml` file.

Example `config.yml`:
```yaml
imports:
    - resource: ../vendor/ql/mcp-panthor/configuration/panthor.yml
    - resource: di.yml
    - resource: routes.yml
    - resource: file.yml # more imports
    - resource: file2.yml # more imports
```

You may also copy this file to your application configuration and include that instead., as it may change
between releases. While Panthor makes every opportunity to follow [semver](http://semver.org/), the configuration may
not.


This configuration provides many boilerplates services.

Service                  | Description
------------------------ | -----------
root                     | The application root. NO TRAILING SLASH.
slim.environment         | Slim\Environment
slim.request             | Slim\Http\Request
slim.response            | Slim\Http\Response
slim.route               | Slim\Route - The matched Slim Route
slim.route.parameters    | An associative array of route parameters.
slim.halt                | An invokeable class used to abort application execution.
slim.not.found           | An invokeable class used to trigger Slim's Not Found functionality

About `slim.halt`:

Usually applications need a way to abort execution of the script. In the case of redirects, errors, or other situations
in which script execution should be terminated early.

This callable requires an http status code. Optionally, you can set the body of the http response. If not provided it
will be empty. Generally this should be avoided in client-facing applications.

Example usage:
```php
call_user_func($halt, 500, 'Internal Server Error');
```

About `slim.not.found`:

When invoked, this class will trigger Slim **Not Found**. It has the same behavior as `slim.halt` in that it halts
further execution of the controller stack.

Example usage:
```php
call_user_func($notFound);
```
