<?php

namespace QL\Panthor\Twig;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Template;

class LazyTwigTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public $twigEnvironment;

    public function setUp()
    {
        $this->twigEnvironment = new Environment(
            new ArrayLoader([
                'path/to/template/file' => 'source 1 {{ var1 }} {% block derp %}derp1{% endblock %}',
                'real/file'             => 'source 2 {{ var2 }} {% block derp %}derp2{% endblock %}',
            ])
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissingTemplateThrowsException()
    {
        $env = Mockery::mock(Environment::class);
        $twig = new LazyTwig($env, new Context);

        $twig->render();
    }

    public function testRenderIsPassedThroughToRealTwig()
    {
        $twig = new LazyTwig($this->twigEnvironment, new Context, 'path/to/template/file');

        $actual = $twig->render();
        $this->assertSame($actual, 'source 1  derp1');
    }

    public function testTemplateSetterOverridesConstructorTemplate()
    {
        $twig = new LazyTwig($this->twigEnvironment, new Context, 'path/to/template/file');
        $twig->setTemplate('real/file');

        $actual = $twig->render([
            'var2' => '2',
        ]);

        $this->assertSame($actual, 'source 2 2 derp2');
    }

    public function testContextIsMergedOnRenderIfProvided()
    {
        $context = new Context([
            'var1' => '1',
        ]);

        $twig = new LazyTwig($this->twigEnvironment, $context, 'path/to/template/file');
        $actual = $twig->render(['goobypls' => 'test']);

        $this->assertSame('test', $context->get('goobypls'));
        $this->assertSame($actual, 'source 1 1 derp1');
    }

    public function testNonRenderMethodIsPassedThrough()
    {
        $twig = new LazyTwig($this->twigEnvironment, new Context, 'path/to/template/file');
        $actual = $twig->hasBlock('derp');

        $this->assertSame(true, $actual);
    }
}
