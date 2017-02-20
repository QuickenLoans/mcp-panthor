<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use PHPUnit_Framework_TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DiTest extends PHPUnit_Framework_TestCase
{
    public $root;

    public function setUp()
    {
        $this->root = __DIR__ . '/../../';
    }

    public function testBuildDI()
    {
        $di = TestDi::buildDi($this->root);

        $this->assertInstanceOf(ContainerInterface::class, $di);
    }

    public function testBuildDIWithModifier()
    {
        $spy = null;
        $callback = function($container) use (&$spy) {
            $spy = $container;
        };

        $di = TestDi::buildDi($this->root, $callback);

        $this->assertSame($spy, $di);
    }

    public function testCacheDI()
    {
        $di = TestDi::buildDi($this->root);

        $cachedDI = TestDi::dumpDi($di, 'MyDIClass');

        $this->assertContains('class MyDIClass extends Container', $cachedDI);
    }

    public function testLoadingCachedDI()
    {
        $containerClass = 'MyDIClass' . mt_rand(1000, 2000);

        $di = TestDi::buildDi($this->root);
        $cachedDI = TestDi::dumpDi($di, $containerClass);

        eval(strstr($cachedDI, "\n"));

        $di = TestDi::getDi($this->root, $containerClass);

        $this->assertInstanceOf($containerClass, $di);
    }
}

class TestDi extends Di
{
    const PRIMARY_CONFIGURATION_FILE = 'configuration/panthor-slim.yml';
}
