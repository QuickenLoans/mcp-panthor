<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ExceptionRenderer;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Panthor\TemplateInterface;

class HTMLRendererTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultTemplateRendersNoBody()
    {
        $this->fail(self::class . ': ExceptionRendererInterface copied from slim 2 is not compatible with slim 3');
        $renderer = new HTMLRenderer;

        ob_start();

        $renderer->render(500, []);

        $output = ob_get_clean();

        $expected = <<<JSON

JSON;
        $this->assertSame($expected, $output);
    }

    public function testRenderedTemplateSetAsBody()
    {
        $this->fail(self::class . ': ExceptionRendererInterface copied from slim 2 is not compatible with slim 3');
        $template = Mockery::mock(TemplateInterface::CLASS, [
            'render' => 'error page'
        ]);
        $renderer = new HTMLRenderer($template);

        ob_start();

        $renderer->render(500, []);

        $output = ob_get_clean();

        $expected = <<<JSON
error page
JSON;
        $this->assertSame($expected, $output);
    }
}
