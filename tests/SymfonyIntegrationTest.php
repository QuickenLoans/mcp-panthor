<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class SymfonyIntegrationTest extends TestCase
{
    public function testContainerCompiles()
    {
        $configRoot = __DIR__ . '/../configuration';

        $container = new ContainerBuilder;
        $builder = new YamlFileLoader($container, new FileLocator($configRoot));
        $builder->load('panthor.yml');
        $builder->load('panthor-slim.yml');

        $container->compile();
    }
}
