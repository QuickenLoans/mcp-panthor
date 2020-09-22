<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteInterface;

/**
 * Converts route configuration into slim routes and attaches them to Slim.
 *
 * Usage:
 * ```php
 * $slim = $di->get('slim');
 * $routeLoader = $di->get('route_loader');
 *
 * $routeLoader($slim);
 * $slim->run();
 * ```
 */
class RouteLoader
{
    /**
     * Default methods allowed if not specified by route. Equivalent to Slim "any"
     *
     * @var array
     */
    private $defaultMethods;

    /**
     * @var array
     */
    private $routes;

    /**
     * @param array $routes
     */
    public function __construct(array $routes = [])
    {
        $this->routes = $routes;

        $this->defaultMethods = [
            'DELETE',
            'GET',
            'HEAD',
            'OPTIONS',
            'PATCH',
            'POST',
            'PUT',
        ];
    }

    /**
     * @param array $routes
     *
     * @return void
     */
    public function addRoutes(array $routes): void
    {
        $this->routes = array_merge($this->routes, $routes);
    }

    /**
     * Load routes into the application.
     *
     * @param RouteCollectorProxyInterface $router
     *
     * @return void
     */
    public function __invoke(RouteCollectorProxyInterface $router): void
    {
        $this->loadRoutes($router, $this->routes);
    }

    /**
     * Load routes into the application.
     *
     * @param RouteCollectorProxyInterface $slim
     * @param array $routes
     *
     * @return void
     */
    public function loadRoutes(RouteCollectorProxyInterface $router, array $routes): void
    {
        // capture as a callable because slim will re-bind $this
        $loader = [$this, 'loadRoutes'];

        foreach ($routes as $name => $details) {
            if ($children = $details['routes'] ?? []) {
                $middlewares = $details['stack'] ?? [];
                $prefix = $details['route'] ?? '';

                $groupLoader = function (RouteCollectorProxyInterface $router) use ($children, $loader) {
                    $loader($router, $children);
                };

                $group = $router->group($prefix, $groupLoader);
                while ($mw = array_pop($middlewares)) {
                    $group->add($mw);
                }

            } else {
                $this->loadRoute($router, $name, $details);
            }
        }
    }

    /**
     * Load a route into the application.
     *
     * @param RouteCollectorProxyInterface $router
     * @param string $name
     * @param array $details
     *
     * @return RouteInterface
     */
    private function loadRoute(RouteCollectorProxyInterface $router, string $name, array $details): RouteInterface
    {
        $methods = $this->methods($details);
        $pattern = $details['route'] ?? '';
        $stack = $details['stack'] ?? [];

        $controller = array_pop($stack);

        $route = $router->map($methods, $pattern, $controller);
        $route->setName($name);

        while ($middleware = array_pop($stack)) {
            $route->add($middleware);
        }

        return $route;
    }

    /**
     * @param array $routeDetails
     *
     * @return string[]
     */
    private function methods(array $routeDetails)
    {
        // No method matches ANY method
        if (!$methods = $routeDetails['method'] ?? []) {
            return $this->defaultMethods;
        }

        if (!is_array($methods)) {
            $methods = [$methods];
        }

        if ($methods === ['GET']) {
            array_push($methods, 'HEAD');
        }

        return $methods;
    }
}
