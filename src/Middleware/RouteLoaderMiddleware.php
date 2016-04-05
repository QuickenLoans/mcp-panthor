<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Middleware;

use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\Exception\Exception;
use QL\Panthor\MiddlewareInterface;
use Slim\App;

/**
 * Convert a flat array into slim routes and attaches them to the slim application.
 *
 * This hook should be attached to the "slim.before.router" event.
 */
class RouteLoaderMiddleware implements MiddlewareInterface
{
    /**
     * A hash of valid http methods. The keys are the methods.
     *
     * @type array
     */
    private $methods;

    /**
     * @type App
     */
    private $slim;

    /**
     * @type ContainerInterface
     */
    private $container;

    /**
     * @type array
     */
    private $routes;

    /**
     * @param ContainerInterface $container
     * @param array $routes
     */
    public function __construct(ContainerInterface $container, array $routes = [])
    {
        $this->slim = $container->get('panthor.slim');
        $this->container = $container;
        $this->routes = $routes;

        // These are the only methods supported by Slim
        $validMethods = ['DELETE', 'GET', 'HEAD', 'OPTIONS', 'PATCH', 'POST', 'PUT'];
        $this->methods = array_fill_keys($validMethods, true);
    }

    /**
     * @param array $routes
     *
     * @return void
     */
    public function addRoutes(array $routes)
    {
        $this->routes = array_merge($this->routes, $routes);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $this->loadRoutes($this->slim, $this->routes);
        return $next($request, $response);
    }

    /**
     * Load routes into the application
     *
     * @param App $slim
     * @param array $routes
     *
     * @return void
     */
    public function loadRoutes(App $slim, $routes = null)
    {
        if (is_null($routes)) {
            $routes = $this->routes;
        }
        foreach ($routes as $name => $details) {

            $methods = $this->methods($details);
            $group = $this->nullable('group', $details);
            $url = $details['route'];
            $stack = $this->nullable('stack', $details);
            if (!is_null($stack)) {
                $stack = $this->convertStackToCallables($details['stack']);
            }

            // Create route
            if (!is_null($group)) {
                //Groups can't be named or have methods, just their constituents.
                $access = $this;
                $route = $slim->group($url, function () use ($slim, $group, $access) {
                    $access->loadRoutes($slim, $group);
                });

            } else {
                $route = $slim->map($methods, $url, array_pop($stack));
                $route->setName($name);
            }

            if (count($stack) > 0) {
                array_map([$route, 'add'], $stack);
            }
        }
    }

    /**
     * Convert an array of keys to middleware callables
     *
     * @param string[] $stack
     *
     * @return callable[]
     */
    private function convertStackToCallables(array $stack)
    {
        $container = $this->container;
        foreach ($stack as &$key) {
            $key = function ($req, $res, $var) use ($container, $key) {
                return call_user_func($container->get($key), $req, $res, $var);
            };
        }

        return $stack;
    }

    /**
     * @param array $routeDetails
     * 
     * @throws Exception
     *
     * @return string[]
     */
    private function methods(array $routeDetails)
    {
        // No method matches ANY method
        if (!$methods = $this->nullable('method', $routeDetails)) {
            return array_keys($this->methods);
        }

        if ($methods && !is_array($methods)) {
            $methods = [$methods];
        }

        return $methods;
    }

    /**
     * @param string $key
     * @param array $data
     *
     * @return mixed|null
     */
    private function nullable($key, array $data)
    {
        if (isset($data[$key])) {
            return $data[$key];
        }

        return null;
    }
}
