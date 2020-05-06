<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use Slim\Routing\RouteCollector;

/**
 * This router overrides how cache settings are set on the Slim Router.
 *
 * This is necessary because we want to use a separate flag value to determine if the route
 * cached is used, but slim will always check that the cache file exist and is writable if provided.
 */
class CacheableRouteCollectorConfigurator
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
     * @param string $cacheFile
     * @param bool $isCacheDisabled
     *
     * @return void
     */
    public function __construct(string $cacheFile, bool $isCacheDisabled)
    {
        $this->cacheFile = $cacheFile;
        $this->isCacheDisabled = $isCacheDisabled;
    }

    /**
     * @param RouteCollector $collector
     *
     * @return void
     */
    public function __invoke(RouteCollector $collector)
    {
        if (!$this->isCacheDisabled) {
            $this->setCacheFile($this->cacheFile);
        }
    }
}
