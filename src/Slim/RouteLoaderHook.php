<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Slim;

use QL\Panthor\Exception;
use Slim\Slim;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Convert a flat array into slim routes and attaches them to the slim application.
 *
 * This hook should be attached to the "slim.before.router" event.
 */
class RouteLoaderHook
{
    /**
     * A hash of valid http methods. The keys are the methods.
     *
     * @type array
     */
    private $methods;

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

    /**
     * Load routes into the application
     *
     * @param Slim $slim
     *
     * @return void
     */
    public function __invoke(Slim $slim)
    {
        foreach ($this->routes as $name => $details) {

            $methods = $this->methods($details);
            $conditions = $this->nullable('conditions', $details);
            $url = $details['route'];
            $stack = $this->convertStackToCallables($details['stack']);

            // Prepend the url to the stack
            array_unshift($stack, $url);

            // Create route
            // Special note: slim is really stupid in the way it uses func_get_args EVERYWHERE
            $route = call_user_func_array([$slim, 'map'], $stack);
            call_user_func_array([$route, 'via'], $methods);

            // Add Name
            $route->name($name);

            // Add Conditions
            if ($conditions) {
                $route->conditions($conditions);
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
        foreach ($stack as &$key) {
            $key = function () use ($key) {
                call_user_func($this->container->get($key));
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
            return ['ANY'];
        }

        if ($methods && !is_array($methods)) {
            $methods = [$methods];
        }

        // check for invalid method types
        foreach ($methods as $method) {
            if (!isset($this->methods[$method])) {
                throw new Exception(sprintf('Unknown HTTP method: %s', $method));
            }
        }

        if ($methods === ['GET']) {
            array_push($methods, 'HEAD');
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
