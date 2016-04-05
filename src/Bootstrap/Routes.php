<?php
/**
 * @copyright ©2005—2016 Quicken Loans Inc. All rights reserved. Trade Secret, Confidential and Proprietary. Any
 *     dissemination outside of Quicken Loans is strictly prohibited.
 */

namespace QL\Panthor\Bootstrap;

use Symfony\Component\DependencyInjection\ContainerInterface;
use QL\Panthor\Middleware\RouteLoaderMiddleware;
use Slim\App as Slim;

class Routes
{
    /**
     * @param $root
     * @param ContainerInterface $container
     *
     * @return ContainerInterface
     */
    public static function cacheRoutes($root, ContainerInterface $container)
    {
        /** @var Slim $slim */
        $slim = $container->get('slim');
        $routes = $container->getParameter('routes');

        /** @var RouteLoaderMiddleware $routeLoader */
        $routeLoader = $container->get('slim.middleware.routes');
        //Used to happen in middleware, but if route is cached, this middleware doesn't need to run.
        $routeLoader->loadRoutes($slim, $routes);

        $router = $slim->getContainer()->get('router');
        $cacheFile = $container->getParameter('routes.cache.file');
        $shouldCache = $container->getParameter('routes.cache');

        $fastRouteDispatcherFactory = new FastRouteDispatcherFactory(
            $router,
            $root,
            $cacheFile,
            $shouldCache
        );

        $cachedFile = $fastRouteDispatcherFactory->getAbsolutePath();
        if (file_exists($cachedFile)) {
            //Need to delete the existing cache file, otherwise fast routes will not cache.
            unlink($cachedFile);
        }

        //saves the cached routes if routes.cache is true
        $fastRouteDispatcherFactory->getDispatcher();
    }
}
