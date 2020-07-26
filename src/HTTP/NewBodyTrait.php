<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTP;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Stream;

trait NewBodyTrait
{
    /**
     * @param ResponseInterface $response
     * @param string $contents
     *
     * @return ResponseInterface
     */
    private function withNewBody(ResponseInterface $response, $contents)
    {
        $body = new Stream(fopen('php://temp', 'r+'));
        $body->write($contents);

        return $response->withBody($body);
    }
}
