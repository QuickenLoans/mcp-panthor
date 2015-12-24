<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Panthor\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Slim;

/**
 * Apache does not pass the Authorization header to PHP by default.
 *
 * This hook will populate the authorization header for apache deployments.
 *
 * It should be attached to the "slim.before" event.
 */
class ApacheAuthorizationHeaderMiddleware
{
    /**
     * @type callable|string
     */
    private $getHeaderFunction;

    /**
     * @param callable|string $getHeaderFunction
     */
    public function __construct($getHeaderFunction = 'apache_request_headers')
    {
        $this->getHeaderFunction = $getHeaderFunction;
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     *
     * @return mixed
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($request->hasHeader('Authorization')) {
            return $next($request, $response);
        }

        if (is_callable($this->getHeaderFunction)) {
            $customHeaders = call_user_func($this->getHeaderFunction);
            if (is_array($customHeaders) && array_key_exists('Authorization', $customHeaders)) {
                $request->withHeader('Authorization', $customHeaders['Authorization']);
            }
        }

        return $next($request, $response);
    }
}
