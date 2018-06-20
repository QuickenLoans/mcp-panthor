<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTPProblem\Renderer;

use PHPUnit\Framework\TestCase;
use QL\Panthor\HTTPProblem\HTTPProblem;
use QL\Panthor\Utility\JSON;

class JSONRendererTest extends TestCase
{
    public function testOptionalPropertiesNotRendered()
    {
        $expectedStatus = 500;
        $expectedHeaders = [
            'Content-Type' => 'application/problem+json'
        ];

        $expectedBody = <<<JSON
{"status":500,"title":"Internal Server Error","detail":"Error ahoy!"}
JSON;

        $problem = new HTTPProblem(500, 'Error ahoy!');

        $renderer = new JSONRenderer;
        $status = $renderer->status($problem);
        $headers = $renderer->headers($problem);
        $body = $renderer->body($problem);

        $this->assertSame(500, $status);
        $this->assertSame($expectedHeaders, $headers);
        $this->assertSame($expectedBody, $body);
    }

    public function testRenderingFullProblem()
    {
        $expectedBody = <<<JSON
{
    "status": 500,
    "title": "Application error code 5021",
    "type": "http://example/problem1.html",
    "detail": "Major Tom, are you receiving me?",
    "instance": "http://example/issue/12345.html",
    "ext1": "data1",
    "ext2": "data2",
    "ext3": "data3"
}
JSON;

        $problem = new HTTPProblem(500, 'Major Tom, are you receiving me?', [
            'ext1' => 'data1',
            'ext2' => 'data2',
            'ext3' => 'data3',
        ]);

        $problem
            ->withTitle('Application error code 5021')
            ->withType('http://example/problem1.html')
            ->withInstance('http://example/issue/12345.html');

        $json = new JSON;
        $json->addEncodingOptions(\JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
        $renderer = new JSONRenderer($json);
        $body = $renderer->body($problem);

        $this->assertSame($expectedBody, $body);
    }
}
