<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Twig;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LazyTwigTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissingTemplateThrowsException()
    {
        $env = Mockery::mock('Twig_Environment');
        $twig = new LazyTwig($env, new Context);

        $twig->render();
    }

    public function testRenderIsPassedThroughToRealTwig()
    {
        $realTwig = Mockery::mock('Twig_Template', ['render' => null]);
        $env = Mockery::mock('Twig_Environment');
        $env
            ->shouldReceive('loadTemplate')
            ->with('path/to/template/file')
            ->andReturn($realTwig)
            ->once();

        $twig = new LazyTwig($env, new Context, 'path/to/template/file');

        $twig->render();
    }

    public function testTemplateSetterOverridesConstructorTemplate()
    {
        $realTwig = Mockery::mock('Twig_Template', ['render' => null]);
        $env = Mockery::mock('Twig_Environment');
        $env
            ->shouldReceive('loadTemplate')
            ->with('real/file')
            ->andReturn($realTwig)
            ->once();

        $twig = new LazyTwig($env, new Context, 'path/to/template/file');
        $twig->setTemplate('real/file');

        $twig->render();
    }

    public function testContextIsMergedOnRenderIfProvided()
    {
        $realTwig = Mockery::mock('Twig_Template', ['render' => null]);
        $env = Mockery::mock('Twig_Environment', ['loadTemplate' => $realTwig]);
        $context = new Context;

        $twig = new LazyTwig($env, $context, 'path/to/template/file');
        $twig->render(['goobypls' => 'test']);

        $this->assertSame('test', $context->get('goobypls'));
    }

    public function testNonRenderMethodIsPassedThrough()
    {
        $realTwig = Mockery::mock('Twig_Template');
        $realTwig
            ->shouldReceive('testing')
            ->once();

        $env = Mockery::mock('Twig_Environment', ['loadTemplate' => $realTwig]);

        $twig = new LazyTwig($env, new Context, 'path/to/template/file');
        $twig->testing();
    }
}
