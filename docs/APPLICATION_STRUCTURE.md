### Application Structure and Configuration

- [Back to Documentation](README.md)
- Application Structure
- [How To Use](USAGE.md)
- [Error Handling](ERRORS.md)
- [Cookies](COOKIES.md)
- [Web Server Configuration](SERVER.md)

```
ROOT
├─ bin
│   └─ executables
│
├─ config
│   ├─ .env
│   ├─ bootstrap.php
│   ├─ config.yaml
│   ├─ di.yml
│   └─ routes.yml
│
├─ public
│   └─ index.php
│
├─ src
│   └─ ... code
│
└─ testing
    ├─ fixtures
    │   └─ ... testing fixtures
    │
    ├─ src
    │   └─ ... testing stubs and mocks
    │
    └─ tests
       └─ ... tests
```

#### config/

The configuration directory contains all configuration files, usually in YAML.

Put environment-specific configuration in the respective file under `environment/`. When your application is deployed
to an environment, the matching file is found and merged into the general application configuration.

`bootstrap.php`
> The common starting point of your application. It is used by the launch file (index.php), and any other scripts that
> need access to the di container or slim application. You may add general application configuration such as error or
> session handlers, ini settings, etc.
>
> Example:
> ```php
> <?php
>
> namespace TestApplication\Bootstrap;
>
> use QL\Panthor\Bootstrap\DI;
> use TestApplication\CachedContainer;
>
> $root = __DIR__ . '/..';
> require_once "${root}/vendor/autoload.php";
>
> // Set Timezone to UTC
> ini_set('date.timezone', 'UTC');
> date_default_timezone_set('UTC');
>
> // Set multibyte encoding
> mb_internal_encoding('UTF-8');
> mb_regex_encoding('UTF-8');
>
> # $dotenv = new Dotenv;
> # $dotenv->load("${root}/config/.env");
>
> $container = DI::getDi($root, [
>     'file'  => "${root}/src/CachedContainer.php",
>     'class' => CachedContainer::class
> ]);
>
> return $container;
> ```

`config.yaml`
> It is used for environment-independent configuration and is the common configuration file that loads all other
> configuration. If you would like to break up your config or DI settings into multiple files to maintain your
> sanity, just add them to the list of imports at the top of the file.
>
> Example:
> ```yaml
> imports:
>     - resource: ../vendor/ql/mcp-panthor/configuration/panthor-slim.yml
>     - resource: ../vendor/ql/mcp-panthor/configuration/panthor.yml
>     - resource: di.yml
>     - resource: routes.yml
>
> parameters:
>     cookie.encryption.secret: '' # 128-character hexademical string. Used for cookie encryption with libsodium.
>
>     # We recommend using Symfony/Dotenv instead!
>     env(PANTHOR_APPROOT): '/full/path/to/application'
> ```

`di.yml`
> Where all DI service definitions should be defined.
>
> Example:
> ```yaml
> services:
>     page.hello_world:
>         class: 'TestApplication\TestController'
> ```

`routes.yml`
> Where all routes should be defined.
>
> Example:
> ```yaml
> parameters:
>     routes:
>         # Basic route
>         # No method will match ANY method
>         hello_world:
>             route: '/'
>             stack: ['page.hello_world']
>
>         # Route with parameter and conditions
>         # example.route_parameter:
>         #     method: 'GET'
>         #     route: '/example/entities/{id:[\d]+}'
>         #     stack: ['page.example']
>
>         # Route that matches multiple methods
>         # example.multiple_methods:
>         #     method:
>         #         - 'GET'
>         #         - 'POST'
>         #         - 'PUT'
>         #     route: '/example/new'
>         #     stack: ['page.example']
>
>         # Route with middleware. The controller is always the last service.
>         # example.middleware:
>         #     method: 'GET'
>         #     route: '/example/remove'
>         #     stack:
>         #         - 'middleware.example'
>         #         - 'page.example'
>
>         # Route Group, contains other routes
>         # Route Groups apply middleware to all routes underneath it
>         # example.group:
>         #     stack: ['middleware.example2']
>         #     route: '/partial-path'
>         #     routes:
>         #         example.page2:
>         #             route: '/example2'
>         #             stack: ['page.example2']
> ```

#### src/

It's where the code goes.

Example controller:
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
>     public function __invoke(ResponseInterface $response, ServerRequestInterface $request)
>     {
>         $response->getBody->write('Hello World!');
>         return $response;
>     }
> }
> ```

#### testing/

The testing directory contains two folders:
`src/` and `tests/`.

`tests/` is typically where phpunit tests are located. Nesting the test directory in this manner
potentially allows multiple testing suites while keeping them isolated.

For example, your testing suite may eventually expand to the following:
```
testing
├─ fixtures
│   └─ ... testing fixtures
│
├─ src
│  └─ ... testing stubs and mocks
│
├─ phpunit-tests
│  └─ ... tests
│
├─ phpunit.xml
│
└─ integration-tests
   └─ ... tests
```

Keeping all testing support within this folder allows you to easily exclude it when creating a dist of your application.

