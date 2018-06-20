<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap\DependencyInjection;

use QL\Panthor\ControllerInterface;
use QL\Panthor\MiddlewareInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;


class PanthorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definitions = $container->getDefinitions();

        foreach ($definitions as $definition) {
            if (is_subclass_of($definition->getClass(), ControllerInterface::class)) {
                $definition->setPublic(true);
            }

            if (is_subclass_of($definition->getClass(), MiddlewareInterface::class)) {
                $definition->setPublic(true);
            }
        }
    }
}