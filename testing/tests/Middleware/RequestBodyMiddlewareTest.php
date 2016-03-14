<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Middleware;

use Mockery;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase;
use QL\Panthor\Utility\Json;
use Slim\Http\Request;
use Slim\Http\Response;

class RequestBodyMiddlewareTest extends PHPUnit_Framework_TestCase
{
    /** @var \Mockery\MockInterface */
    private $di;
    /** @var \Mockery\MockInterface */
    private $request;
    /** @var \Mockery\MockInterface */
    private $response;
    /** @var \Mockery\MockInterface */
    private $json;

    public function setUp()
    {
        $this->di = Mockery::mock(ContainerInterface::class);
        $this->request = Mockery::mock(Request::class);
        $this->response = Mockery::mock(Response::class);
        $this->json = Mockery::mock(Json::class);
    }

    /**
     * @expectedException \QL\Panthor\Exception\RequestException
     */
    public function testUnsupportedType()
    {
        $this->request
            ->shouldReceive('getHeader')
            ->with('contentType')
            ->andReturn('text/plain');

        $mw = new RequestBodyMiddleware($this->di, $this->json, 'service.name');
        try {
            $mw($this->request, $this->response, function(){});
        } catch (\Exception $e)
        {
            var_dump($e);
        }
    }

    public function testEmptyPostMeansPartyTime()
    {
        $this->request
            ->shouldReceive('getHeader')
            ->with('contentType')
            ->andReturn('application/json');
        $this->request
            ->shouldReceive('getBody->getContents')
            ->andReturn('{}');

        $this->json
            ->shouldReceive('__invoke')
            ->with('{}')
            ->andReturn([]);

        $this->di
            ->shouldReceive('set')
            ->with('service.name', [RequestBodyMiddleware::NOFUNZONE])
            ->andReturn([]);

        $mw = new RequestBodyMiddleware($this->di, $this->json, 'service.name');
        $mw($this->request, $this->response, function(){});
    }

    public function testEmptyJsonWithDefaultKeys()
    {
        $this->request
            ->shouldReceive('getHeader')
            ->with('contentType')
            ->andReturn('application/json');
        $this->request
            ->shouldReceive('getBody->getContents')
            ->andReturn('{}');

        $this->json
            ->shouldReceive('__invoke')
            ->with('{}')
            ->andReturn([]);

        $this->di
            ->shouldReceive('set')
            ->with('service.name', ['key1' => null, 'key2' => null])
            ->andReturn([]);

        $mw = new RequestBodyMiddleware($this->di, $this->json, 'service.name');
        $mw->setDefaultKeys(['key1', 'key2']);
        $mw($this->request, $this->response, function(){});
    }
}
