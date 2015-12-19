<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor;

use Slim\Http\Request;
use Slim\Http\Response;

interface ControllerInterface
{
    /**
     * The primary action of this controller. Any return from this method is ignored.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $args);
}
