### Application Structure and Configuration

```
ROOT
├─ bin
│   ├─ cache-container
│   ├─ cache-routes
│   ├─ cache-templates
│   └─ ... other executables
├─ config
│   ├─ .env
│   ├─ bootstrap.php
│   ├─ config.yaml
│   ├─ di.php
│   └─ routes.yml
├─ public
│   └─ index.php
└─ src
    └─ HelloWorldController.php
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
> Example: [starter-kit/config/bootstrap.php](../starter-kit/config/bootstrap.php)

`config.yaml`
> It is used for environment-independent configuration and is the common configuration file that loads all other
> configuration. If you would like to break up your config or DI settings into multiple files to maintain your
> sanity, just add them to the list of imports at the top of the file.
>
> Example: [starter-kit/config/config.yaml](../starter-kit/config/config.yaml)

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
> namespace ExampleApplication;
>
> use Psr\Http\Message\ResponseInterface;
> use Psr\Http\Message\ServerRequestInterface;
> use QL\Panthor\ControllerInterface;
>
> class HelloWorldController implements ControllerInterface
> {
>     public function __invoke(ResponseInterface $response, ServerRequestInterface $request)
>     {
>         $response->getBody->write('Hello World!');
>         return $response;
>     }
> }
> ```
