<?php

namespace QL\Panthor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Config\FileLocator;

class SymfonyIntegrationTest extends TestCase
{
    public function testContainerCompilesWithPHP()
    {
        $configRoot = __DIR__ . '/../config';

        $container = new ContainerBuilder;
        $builder = new PhpFileLoader($container, new FileLocator($configRoot));
        $builder->load('panthor.php');
        $builder->load('slim.php');

        $container->compile();
    }
}
