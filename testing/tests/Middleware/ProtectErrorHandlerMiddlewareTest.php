<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Middleware;

use Mockery;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ProtectErrorHandlerMiddlewareTest extends PHPUnit_Framework_TestCase
{
    public function testErrorHandlerIsReset()
    {
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);

        $handler = function() {};

        $middleware = new ProtectErrorHandlerMiddleware($handler);

        $existingHandler = set_error_handler(null);

        $middleware($request, $response, $handler);

        $appHandler = set_error_handler($existingHandler);

        $this->assertSame($handler, $appHandler);

    }
}
