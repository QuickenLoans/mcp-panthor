<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use InvalidArgumentException;
use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Panthor\Exception\Exception;
use Slim\App;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GlobalMiddlewareLoaderTest extends PHPUnit_Framework_TestCase
{
    public function testMiddlewareIsAttached()
    {
        $middlewares = [
            'mw1',
            'mw2.testing'
        ];

        $mock1 = function() {return 1;};
        $mock2 = function() {return 2;};

        $di = Mockery::mock(ContainerInterface::class);
        $di
            ->shouldReceive('get')
            ->with('mw2.testing')
            ->andReturn($mock2);
        $di
            ->shouldReceive('get')
            ->with('mw1')
            ->andReturn($mock1);

        $slim = Mockery::mock(App::class);
        $slim
            ->shouldReceive('add')
            ->with($mock1)
            ->once();
        $slim
            ->shouldReceive('add')
            ->with($mock2)
            ->once();

        $loader = new GlobalMiddlewareLoader($di, $middlewares);

        $actual = $loader->attach($slim);

        $this->assertSame($slim, $actual);
    }

    public function testMissingMiddlewareThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to retrieve global middleware "mw2.testing" from DI container.');

        $middlewares = [
            'mw1',
            'mw2.testing'
        ];

        $di = Mockery::mock(ContainerInterface::class);
        $di
            ->shouldReceive('get')
            ->with('mw2.testing')
            ->andThrow(new InvalidArgumentException('msg'));

        $slim = Mockery::mock(App::class);

        $loader = new GlobalMiddlewareLoader($di, $middlewares);
        $loader->attach($slim);
    }
}
