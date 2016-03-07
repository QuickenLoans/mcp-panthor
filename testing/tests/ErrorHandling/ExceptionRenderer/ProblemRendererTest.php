<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ExceptionRenderer;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Panthor\Exception\HTTPProblemException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App as Slim;

class ProblemRendererTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultRendererWithoutContext()
    {
        $status = 500;
        $context = [];
        $response = Mockery::mock(ResponseInterface::class);
        $renderer = new ProblemRenderer;

        $rendered = <<<JSON
{
    "status": 500,
    "title": "Internal Server Error",
    "detail": "Unknown error"
}
JSON;
        ob_start();

        $response->shouldReceive('withStatus')
            ->with($status, json_encode($context))
            ->andReturn($response)
            ->once();
        $response->shouldReceive('withHeader')
            ->andReturn($response);
        $response->shouldReceive('getBody')
            ->andReturn($rendered)
            ->once();

        $renderer->render($response, $status, $context);

        $output = ob_get_clean();

        $this->assertSame($rendered, $output);
    }

    public function testRenderingWithProblem()
    {
        $status = 403;
        $response = Mockery::mock(ResponseInterface::class);
        $renderer = new ProblemRenderer;
        $rendered = <<<JSON
{
    "status": 403,
    "title": "test title",
    "type": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
    "detail": "This action is not allowed",
    "instance": "http://example.com/12345",
    "data1": "abcd",
    "data2": 1234
}
JSON;

        $exception = new HTTPProblemException($status, 'This action is not allowed', [
            'data1' => 'abcd',
            'data2' => 1234
        ]);

        $context = [
            'exception' => $exception
        ];

        $exception
            ->problem()
            ->withTitle('test title')
            ->withType('http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html')
            ->withInstance('http://example.com/12345');

        ob_start();

        $response->shouldReceive('withStatus')
            ->with($status, $rendered)
            ->andReturn($response)
            ->once();
        $response->shouldReceive('withHeader')
            ->andReturn($response);
        $response->shouldReceive('getBody')
            ->andReturn($rendered)
            ->once();

        $renderer->render($response, 500, $context);

        $output = ob_get_clean();

        $this->assertSame($rendered, $output);
    }

    public function testRendererWithSlimAttached()
    {
        $status = 500;
        $rendered = 'rendered';
        $content = [];
        $request = Mockery::mock(RequestInterface::CLASS, ['isHead' => true]);
        $response = Mockery::mock(ResponseInterface::class);
        $slim = Mockery::mock(Slim::CLASS, [
            'response' => $response,
            'config' => '1.0',
        ]);

        $response->shouldReceive('withStatus')
            ->andReturn($response)
            ->once();
        $response->shouldReceive('withHeader')
            ->andReturn($response);
        $response->shouldReceive('getBody')
            ->andReturn($rendered);

        $request->shouldReceive('getMethod')
            ->andReturn('get');

        $slim->shouldReceive("getContainer->get")
            ->with('request')
            ->andReturn($request);
        $slim->shouldReceive('respond')
            ->with($response)
            ->once();

        $renderer = new ProblemRenderer;
        $renderer->attachSlim($slim);

        ob_start();

        $renderer->render($response, $status, $content);

        $output = ob_get_clean();

        $this->assertSame($rendered, $output);
    }

}
