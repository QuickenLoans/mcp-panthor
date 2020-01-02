<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use Slim\App;
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
    public function addRoutes(array $routes)
    {
        $this->routes = array_merge($this->routes, $routes);
    }

    /**
     * Load routes into the application.
     *
     * @param App $slim
     *
     * @return void
     */
    public function __invoke(App $slim)
    {
        $this->loadRoutes($slim, $this->routes);
    }

    /**
     * Load routes into the application.
     *
     * @param App $slim
     * @param array $routes
     *
     * @return void
     */
    public function loadRoutes(App $slim, array $routes)
    {
        // capture as a callable because slim will re-bind $this
        $loader = [$this, 'loadRoutes'];

        foreach ($routes as $name => $details) {
            if ($children = $details['routes'] ?? []) {
                $middlewares = $details['stack'] ?? [];
                $prefix = $details['route'] ?? '';

                $groupLoader = function () use ($slim, $children, $loader) {
                    $loader($slim, $children);
                };

                $group = $slim->group($prefix, $groupLoader);
                while ($mw = array_pop($middlewares)) {
                    $group->add($mw);
                }

            } else {
                $this->loadRoute($slim, $name, $details);
            }
        }
    }

    /**
     * Load a route into the application.
     *
     * @param App $slim
     * @param string $name
     * @param array $details
     *
     * @return RouteInterface
     */
    private function loadRoute(App $slim, $name, array $details)
    {
        $methods = $this->methods($details);
        $pattern = $details['route'] ?? '';
        $stack = $details['stack'] ?? [];

        $controller = array_pop($stack);

        $route = $slim->map($methods, $pattern, $controller);
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
