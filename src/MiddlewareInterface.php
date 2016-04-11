<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MiddlewareInterface
{
    /**
     * The primary action of this middleware. Any return from this method is ignored.
     *
     * @return null
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next);
}
