<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use Mockery;
use PHPUnit_Framework_TestCase;
use Slim\App;
use Interop\Container\ContainerInterface;

class SlimConfiguratorTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $slim = Mockery::mock(App::CLASS);
        $di = Mockery::mock(ContainerInterface::CLASS);

        $slim
            ->shouldReceive('add')
            ->with(Mockery::type('callable'))
            ->once();
        $slim
            ->shouldReceive('add')
            ->with(Mockery::type('callable'))
            ->once();
        $slim
            ->shouldReceive('add')
            ->with(Mockery::type('callable'))
            ->once();

        $configurator = new SlimConfigurator($di, [
            'hook1',
            'hook2',
            'hook3'
        ]);

        $configurator->configure($slim);
    }
}
