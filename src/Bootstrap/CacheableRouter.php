<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use Closure;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Slim\Router;

class CacheableRouter extends Router
{
    /**
     * @var string
     */
    private $cacheFile;

    /**
     * @var bool
     */
    private $isCacheDisabled;

    /**
     * @return void
     */
    public function initializeDispatcher()
    {
        $this->dispatcher = $this->createDispatcher();
    }

    /**
     * @param string $cacheFile
     * @param bool $isCacheDisabled
     */
    public function setCaching($cacheFile, $isCacheDisabled)
    {
        $this->cacheFile = $cacheFile;
        $this->isCacheDisabled = $isCacheDisabled;
    }

    /**
     * @return Dispatcher
     */
    protected function createDispatcher()
    {
        if ($this->dispatcher) {
            return $this->dispatcher;
        }

        $options = [
          'routeParser' => $this->routeParser,
          'cacheFile' => $this->cacheFile
        ];

        if ($this->cacheFile && !$this->isCacheDisabled) {
            return \FastRoute\cachedDispatcher($this->fastRouteDefinitionCallback(), $options);
        }

        return \FastRoute\simpleDispatcher($this->fastRouteDefinitionCallback(), $options);
    }

    /**
     * @return Closure
     */
    private function fastRouteDefinitionCallback()
    {
        $callback = function (RouteCollector $r) {
            foreach ($this->getRoutes() as $route) {
                $r->addRoute($route->getMethods(), $route->getPattern(), $route->getIdentifier());
            }
        };

        return $callback;
    }
}
