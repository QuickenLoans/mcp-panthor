<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling;

use Exception;
use Psr\Http\Message\ResponseInterface;

interface ExceptionRendererInterface
{
    /**
     * Render a exception data to the response.
     *
     * @param ResponseInterface $response
     * @param int $status
     * @param array $context
     *
     * @return void
     */
    public function render(ResponseInterface $response, $status, array $context);
}
