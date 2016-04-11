<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use Slim\Router;

class FastRouteDispatcherFactory
{
    const CACHE_PATTERN = '%s/%s';

    /** @var Router $router */
    private $router;

    /** @var string $root */
    private $root;

    /** @var bool|false $cacheRoutes */
    private $cacheRoutes;

    /** @var string $cacheFile */
    private $cacheFile;

    /**
     * FastRouteDispatcherFactory constructor.
     *
     * @param Router $router
     * @param string $root
     * @param string $cacheFile
     * @param bool|false $cacheRoutes
     */
    public function __construct(
        Router $router,
        $root,
        $cacheFile = '',
        $cacheRoutes = false
    ) {
        $this->router = $router;
        $this->root = $root;
        $this->cacheRoutes = $cacheRoutes;
        $this->cacheFile = $cacheFile;
    }

    /**
     * Get the configured dispatcher
     *
     * @return Dispatcher
     */
    public function getDispatcher()
    {
        if ($this->cacheRoutes && !empty($this->cacheFile)) {
            return $this->loadCachedDispatcher();
        }
        return $this->getDefaultDispatcher();
    }

    /**
     * Get the cached dispatcher
     *
     * @param array $options
     *
     * @return Dispatcher
     */
    public function loadCachedDispatcher($options = [])
    {
        $options['cacheFile'] = $this->getAbsolutePath();
        return \FastRoute\cachedDispatcher($this->routeLoader(), $options);
    }

    /**
     * Get the default (not-cached) dispatcher
     *
     * @param array $options
     *
     * @return Dispatcher
     */
    public function getDefaultDispatcher($options = [])
    {
        return \FastRoute\simpleDispatcher($this->routeLoader(), $options);
    }

    /**
     * Gets the absolute location of the cachefile if it exists.
     *
     * @return string|null
     */
    public function getAbsolutePath()
    {
        if (!empty($this->cacheFile)) {
            return sprintf(self::CACHE_PATTERN, rtrim($this->root, "/"), ltrim($this->cacheFile));
        }
        return null;
    }

    /**
     * Rips the defined routes out of slim and gives them to fast route.
     */
    private function routeLoader()
    {
        return function (RouteCollector $routeCollector) {
            foreach ($this->router->getRoutes() as $route) {
                $routeCollector->addRoute($route->getMethods(), $route->getPattern(), $route->getIdentifier());
            }
        };
    }
}
