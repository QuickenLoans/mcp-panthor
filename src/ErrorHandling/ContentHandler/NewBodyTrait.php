<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ContentHandler;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Body;

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
        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($contents);

        return $response->withBody($body);
    }
}
