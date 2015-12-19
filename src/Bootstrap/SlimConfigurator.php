<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use Closure;
use Slim\App;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
        foreach ($this->hooks as $event => $hooks) {
            foreach ($hooks as $hook) {
                $slim->hook($event, $this->hookClosure($slim, $hook));
            }
        }
    }

    /**
     * Lazy loader for the actual hook services.
     *
     * @param App $slim
     * @param string $key
     *
     * @return Closure
     */
    private function hookClosure(App $slim, $key)
    {
        return function() use ($slim, $key) {
            $service = $this->di->get($key);
            call_user_func($service, $slim);
        };
    }
}
