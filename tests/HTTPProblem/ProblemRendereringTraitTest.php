<?php

namespace QL\Panthor\HTTPProblem;

use PHPUnit\Framework\TestCase;
use QL\Panthor\HTTPProblem\Renderer\JSONRenderer;
use Slim\Psr7\Factory\ResponseFactory;

class ProblemRendereringTraitTest extends TestCase
{
    public $dummy;
    public $response;
    public $renderer;

    public function setUp(): void
    {
        $this->response = (new ResponseFactory)->createResponse();
        $this->renderer = new JSONRenderer;

        $this->dummy = new class {
            use ProblemRenderingTrait {
                renderProblem as public;
            }
        };
    }

    public function testRenderingProblem()
    {
        $problem = new HTTPProblem(418, 'Something bad happened!', [
            'extra_context' => 'data1',
            'test_extension' => 'data2'
        ]);

        $rendering = $this->dummy;
        $output = $rendering->renderProblem($this->response, $this->renderer, $problem);

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $output->getProtocolVersion();

        $expectedStatusCode = 418;
        $actualStatusCode = $output->getStatusCode();

        $expectedReasonPhrase = 'I\'m a teapot';
        $actualReasonPhrase = $output->getReasonPhrase();

        $expectedHeaders = [
            'Content-Type' => [
                'application/problem+json'
            ]
        ];
        $actualHeaders = $output->getHeaders();

        $expectedBody = '{"status":418,"title":"I\u0027m a teapot","detail":"Something bad happened!","extra_context":"data1","test_extension":"data2"}';
        $actualBody = $output->getBody();
        $actualBody->rewind();

        $this->assertSame($expectedHTTPVersion, $actualHTTPVersion);
        $this->assertSame($expectedStatusCode, $actualStatusCode);
        $this->assertSame($expectedReasonPhrase, $actualReasonPhrase);
        $this->assertSame($expectedHeaders, $actualHeaders);
        $this->assertSame($expectedBody, $actualBody->getContents());
    }
}
