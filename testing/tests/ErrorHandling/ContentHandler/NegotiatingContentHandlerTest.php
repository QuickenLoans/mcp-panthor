<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ContentHandler;

use ErrorException;
use Mockery;
use PHPUnit_Framework_TestCase;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class NegotiatingContentHandlerTest extends PHPUnit_Framework_TestCase
{
    private $request;
    private $response;

    public function setUp()
    {
        $this->request = Request::createFromEnvironment(Environment::mock());
        $this->response = new Response;
    }

    public function testNotFoundWithNoSetHandlersUsesDefaultList()
    {
        $this->request = $this->request->withHeader('Accept', 'application/json');

        $handler = new NegotiatingContentHandler;
        $response = $handler->handleNotFound($this->request, $this->response);

        $expected = <<<HTML
HTTP/1.1 404 Not Found
Content-Type: application/json

{"message":"Not Found"}
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testNotAllowedWithEmptyListOnlyUsesPlaintext()
    {
        $this->request = $this->request->withHeader('Accept', 'application/json');

        $handler = new NegotiatingContentHandler([]);
        $response = $handler->handleNotAllowed($this->request, $this->response, ['PATCH', 'STEVE']);

        $expected = <<<HTML
HTTP/1.1 405 Method Not Allowed
Content-Type: text/plain

Method not allowed.
Allowed methods: PATCH, STEVE
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testHandleExceptionNoMatchUsesFirstInList()
    {
        $this->request = $this->request->withHeader('Accept', 'weird/type');
        $ex = new ErrorException('exception message');

        $handler = new NegotiatingContentHandler([
            'application/json' => new JSONContentHandler,
            'text/plain' => new PlainTextContentHandler
        ]);

        $response = $handler->handleException($this->request, $this->response, $ex);

        $expected = <<<HTML
HTTP/1.1 500 Internal Server Error
Content-Type: application/json

{"error":"Application Error"}
HTML;

        $this->assertSame($expected, (string) $response);
    }
}
