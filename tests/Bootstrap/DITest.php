<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DITest extends TestCase
{
    public $root;

    public function setUp()
    {
        $this->root = __DIR__ . '/../../';
    }

    public function testBuildDI()
    {
        $di = TestDI::buildDI($this->root);

        $this->assertInstanceOf(ContainerInterface::class, $di);
    }

    public function testCacheDI()
    {
        $di = TestDI::buildDI($this->root);

        $cachedDI = TestDI::cacheDI($di, [ 'class' => 'MyDIClass' ]);

        $this->assertContains('class MyDIClass extends Container', $cachedDI);
    }

    public function invalidOptionsForGetReturnsNull()
    {
        $di = TestDI::getDI($this->root, []);

        $this->assertSame(null, $di);
    }

    public function invalidOptionsForCacheReturnsNull()
    {
        $di = TestDI::buildDI($this->root);

        $cachedDI = TestDI::cacheDI($di, []);

        $this->assertSame(null, $cachedDI);
    }

    public function testLoadingCachedDI()
    {
        $cacheOptions = [
            'class' => 'MyDIClass' . mt_rand(1000, 2000)
        ];

        $di = TestDI::buildDI($this->root);
        $cachedDI = TestDI::cacheDI($di, $cacheOptions);

        eval(strstr($cachedDI, "\n"));

        $di = TestDI::getDI($this->root, $cacheOptions);

        $this->assertInstanceOf($cacheOptions['class'], $di);
    }
}

class TestDI extends DI
{
    const PRIMARY_CONFIGURATION_FILE = 'configuration/panthor-slim.yml';
}
