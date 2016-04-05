<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use Interop\Container\ContainerInterface;
use Slim\App;

/**
 * This is how we configure Slim directly after it is instantiated. This is the Slim equivalent of Silex providers.
 *
 * Please note: hooks must be passed in this form:
 * [
 *     'SLIM_HOOK_TYPE_1' => ['SERVICE_KEY_1', 'SERVICE_KEY_2'],
 *     'SLIM_HOOK_TYPE_2' => ['SERVICE_KEY_3']
 * ]
 *
 */
class SlimConfigurator
{
    /**
     * @type ContainerInterface
     */
    private $di;

    /**
     * @type array
     */
    private $hooks;

    /**
     * @param ContainerInterface $di
     * @param array $hooks
     */
    public function __construct(ContainerInterface $di, array $hooks)
    {
        $this->di = $di;
        $this->hooks = $hooks;
    }

    /**
     * @param App $slim
     *
     * @return void
     */
    public function configure(App $slim)
    {
        $container = $this->di;
        foreach ($this->hooks as $hook) {
            //Lazy load all services in case they haven't been created yet.
            $slim->add(function($request, $response, $next) use ($slim, $container, $hook) {
                return call_user_func($container->get($hook), $request, $response, $next);
            });
        }
    }

}
