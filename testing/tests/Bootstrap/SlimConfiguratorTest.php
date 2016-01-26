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
        $this->fail(self::class . 'configurator is dumb :( ');
        $slim = Mockery::mock(App::CLASS);
        $di = Mockery::mock(ContainerInterface::CLASS);

        $slim
            ->shouldReceive('hook')
            ->with('event1', Mockery::type('callable'))
            ->twice();
        $slim
            ->shouldReceive('hook')
            ->with('event2', Mockery::type('callable'))
            ->once();
        $slim
            ->shouldReceive('hook')
            ->with('event3', Mockery::type('callable'))
            ->never();

        $configurator = new SlimConfigurator($di, [
            'event1' => ['hook1', 'hook2'],
            'event2' => ['hook3'],
            'event3' => []
        ]);

        $configurator->configure($slim);
    }
}
