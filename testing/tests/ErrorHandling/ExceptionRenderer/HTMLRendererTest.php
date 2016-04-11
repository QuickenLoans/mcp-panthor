<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ExceptionRenderer;

use Mockery;
use Psr\Http\Message\ResponseInterface;
use PHPUnit_Framework_TestCase;
use QL\Panthor\TemplateInterface;

class HTMLRendererTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultTemplateRendersNoBody()
    {
        $renderer = new HTMLRenderer;
        $rendered = '';
        $status = 500;
        $response = Mockery::mock(ResponseInterface::class,[
            'getBody' => $rendered
        ]);

        $response->shouldReceive('withStatus')
            ->with($status, $rendered)
            ->andReturn($response)
            ->once();
        $response->shouldReceive('withHeader')
            ->with('Content-Type', 'text/html')
            ->andReturn($response)
            ->once();

        ob_start();

        $renderer->render($response, $status, []);

        $output = ob_get_clean();

        $expected = <<<JSON

JSON;
        $this->assertSame($expected, $output);
    }

    public function testRenderedTemplateSetAsBody()
    {
        $rendered = 'error page';
        $template = Mockery::mock(TemplateInterface::CLASS, [
            'render' => $rendered
        ]);
        $renderer = new HTMLRenderer($template);

        $status = 500;

        $response = Mockery::mock(ResponseInterface::class,[
            'getBody' => $rendered
        ]);

        $response->shouldReceive('withStatus')
            ->with($status, $rendered)
            ->andReturn($response)
            ->once();
        $response->shouldReceive('withHeader')
            ->with('Content-Type', 'text/html')
            ->andReturn($response)
            ->once();

        ob_start();

        $renderer->render($response, $status, []);

        $output = ob_get_clean();

        $expected = <<<JSON
$rendered
JSON;
        $this->assertSame($expected, $output);
    }
}
