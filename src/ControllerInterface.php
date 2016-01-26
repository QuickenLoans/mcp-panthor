<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ControllerInterface
{
    /**
     * The primary action of this controller. Any return from this method is ignored.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param $args
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, $args);
}
