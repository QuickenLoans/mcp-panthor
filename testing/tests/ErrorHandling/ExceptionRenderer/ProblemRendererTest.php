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
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Helper\Set;
use Slim\App as Slim;

class ProblemRendererTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultRendererWithoutContext()
    {
        $this->fail(self::class . ': ExceptionRendererInterface copied from slim 2 is not compatible with slim 3');
        $renderer = new ProblemRenderer;

        ob_start();

        $renderer->render(500, []);

        $output = ob_get_clean();

        $expected = <<<JSON
{
    "status": 500,
    "title": "Internal Server Error",
    "detail": "Unknown error"
}
JSON;
        $this->assertSame($expected, $output);
    }

    public function testRenderingWithProblem()
    {
        $this->fail(self::class . ': ExceptionRendererInterface copied from slim 2 is not compatible with slim 3');
        $renderer = new ProblemRenderer;

        $exception = new HTTPProblemException(403, 'This action is not allowed', [
            'data1' => 'abcd',
            'data2' => 1234
        ]);

        $exception
            ->problem()
            ->withTitle('test title')
            ->withType('http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html')
            ->withInstance('http://example.com/12345');

        ob_start();

        $renderer->render(500, [
            'exception' => $exception
        ]);

        $output = ob_get_clean();

        $expected = <<<JSON
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
        $this->assertSame($expected, $output);
    }

    public function testRendererWithSlimAttached()
    {
        $this->fail(self::class . ': ExceptionRendererInterface copied from slim 2 is not compatible with slim 3');
        $request = Mockery::mock(Request::CLASS, ['isHead' => true]);
        $response = Mockery::mock(Response::CLASS);
        $slim = Mockery::mock(Slim::CLASS, [
            'request' => $request,
            'response' => $response,
            'config' => '1.0',
        ]);

        $response->headers = new Set;

        $response
            ->shouldReceive('setStatus')
            ->with(500)
            ->once();
        $response
            ->shouldReceive('setBody')
            ->once();
        $response
            ->shouldReceive('finalize')
            ->andReturn([500, [], 'body']);

        $renderer = new ProblemRenderer;
        $renderer->attachSlim($slim);

        ob_start();

        $renderer->render(500, []);

        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

}
