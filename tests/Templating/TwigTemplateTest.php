<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Templating;

use Mockery;
use PHPUnit\Framework\TestCase;
use QL\Panthor\Twig\Context;
use Twig_Environment;
use Twig_Loader_Array;

class TwigTemplateTest extends TestCase
{
    public $twig;

    public function setUp()
    {
        $this->twig = new Twig_Environment(new Twig_Loader_Array([]));
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
