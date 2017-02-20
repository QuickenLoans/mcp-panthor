<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTPProblem;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Panthor\HTTPProblem\Renderer\JSONRenderer;
use Slim\Http\Response;

class ProblemRendereringTraitTest extends PHPUnit_Framework_TestCase
{
    public $response;
    public $renderer;

    public function setUp()
    {
        $this->response = new Response;
        $this->renderer = new JSONRenderer;
    }

    public function testRenderingProblem()
    {
        $problem = new HTTPProblem(418, 'Something bad happened!', [
            'extra_context' => 'data1',
            'test_extension' => 'data2'
        ]);

        $rendering = new ProblemRenderingTraitStub;
        $output = $rendering->renderProblem($this->response, $this->renderer, $problem);
        $expected = <<<'HTTP'
HTTP/1.1 418 I'm a teapot
Content-Type: application/problem+json

{"status":418,"title":"I\u0027m a teapot","detail":"Something bad happened!","extra_context":"data1","test_extension":"data2"}
HTTP;

        $this->assertSame($expected, (string) $output);

    }
}

class ProblemRenderingTraitStub
{
    use ProblemRenderingTrait {
        renderProblem as public;
    }
}
