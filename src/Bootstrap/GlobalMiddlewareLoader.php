<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use Exception as BaseException;
use QL\Panthor\Exception\Exception;
use Slim\App;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Slim modifier to attach Global Middleware in the correct order.
 */
class GlobalMiddlewareLoader
{
    /**
     * @var ContainerInterface
     */
    private $di;

    /**
     * @var string[]
     */
    private $middleware;

    /**
     * @param ContainerInterface $di
     * @param array $middleware
     */
    public function __construct(ContainerInterface $di, array $middleware)
    {
        $this->di = $di;
        $this->middleware = $middleware;
    }

    /**
     * @param App $slim
     *
     * @return App
     */
    public function attach(App $slim)
    {
        $middlewares = array_reverse($this->middleware);

        foreach ($middlewares as $middleware) {
            $service = $this->getService($middleware);
            $slim->add($service);
        }

        return $slim;
    }

    /**
     * @param string $name
     *
     * @return callable|object
     */
    private function getService($name)
    {
        try {
            $service = $this->di->get($name);

        } catch (BaseException $e) {
            $msg = sprintf('Failed to retrieve global middleware "%s" from DI container.', $name);
            throw new Exception($msg, $e->getCode(), $e);
        }

        return $service;
    }
}
