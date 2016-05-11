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

/**
 * This router overrides how cache settings are set on the Slim Router.
 *
 * We do this because Slim ensures the cache file directory is writeable, whereas we prefer to generate cached
 * routes during a build step, and NEVER generate routes on the web server.
 */
class CacheableRouter extends Router
{
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
        if ($isCacheDisabled) {
            $this->setCacheFile(false);
        } else {
            $this->cacheFile = $cacheFile;
        }
    }
}
