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
        $di = TestDi::buildDI($this->root);

        $this->assertInstanceOf(ContainerInterface::class, $di);
    }

    public function testCacheDI()
    {
        $di = TestDi::buildDI($this->root);

        $cachedDI = TestDi::cacheDI($di, [ 'class' => 'MyDIClass' ]);

        $this->assertContains('class MyDIClass extends Container', $cachedDI);
    }

    public function testLoadingCachedDI()
    {
        $cacheOptions = [
            'class' => 'MyDIClass' . mt_rand(1000, 2000)
        ];

        $di = TestDi::buildDI($this->root);
        $cachedDI = TestDi::cacheDI($di, $cacheOptions);

        eval(strstr($cachedDI, "\n"));

        $di = TestDi::getDI($this->root, $cacheOptions);

        $this->assertInstanceOf($cacheOptions['class'], $di);
    }
}

class TestDi extends Di
{
    const PRIMARY_CONFIGURATION_FILE = 'configuration/panthor-slim.yml';
}
