<?php

namespace QL\Panthor\Templating;

use PHPUnit\Framework\TestCase;
use QL\Panthor\Twig\Context;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigTemplateTest extends TestCase
{
    public $twig;

    public function setUp()
    {
        $this->twig = new Environment(new ArrayLoader([]));
    }

    public function testTwigTemplateRenders()
    {
        $twig = $this->twig->createTemplate('{{ a }}{{ b }}{{ c }}');
        $template = new TwigTemplate($twig);

        $rendered = $template->render([
            'a' => 'he',
            'b' => 'll',
            'c' => 'o',
        ]);

        $this->assertSame('hello', $rendered);
    }

    public function testTwigTemplateCorrectStoresContext()
    {
        $twig = $this->twig->createTemplate('{{ a }}{{ b }}{{ c }}');

        $context = new Context(['b' => 'll']);
        $template = new TwigTemplate($twig, $context);

        $rendered = $template->render([
            'a' => 'he',
            'c' => 'o',
        ]);

        $this->assertSame('hello', $rendered);
    }
}
