<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Utility;

use Psr\Http\Message\UriInterface;
use Slim\Interfaces\RouteParserInterface;

class URI
{
    /**
     * @var RouteParserInterface
     */
    private $router;

    /**
     * @param RouteParserInterface $router
     */
    public function __construct(RouteParserInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Get a query parameter from a PSR-7 UriInterface
     *
     * @param UriInterface $uri
     * @param string $param
     *
     * @return string|array|null
     */
    public function getQueryParam(UriInterface $uri, string $param)
    {
        if (!$query = $uri->getQuery()) {
            return null;
        }

        parse_str($query, $params);
        if (!array_key_exists($param, $params)) {
            return null;
        }

        return $params[$param];
    }

    /**
     * Get the relative URL for a given route name.
     *
     * @param string $route
     * @param array $params
     * @param array $query
     *
     * @return string
     */
    public function uriFor(string $route, array $params = [], array $query = [])
    {
        if (!$route) {
            return '';
        }

        return $this->router->relativeUrlFor($route, $params, $query);
    }

    /**
     * Get the absolute URL for a given route name.
     * You must provide the current request Uri to retrieve the scheme and host.
     *
     * @param UriInterface $uri
     * @param string $route
     * @param array $params
     * @param array $query
     *
     * @return string
     */
    public function absoluteURIFor(UriInterface $uri, string $route, array $params = [], array $query = [])
    {
        $path = $this->uriFor($route, $params);

        return (string) $uri
            ->withUserInfo('')
            ->withPath($path)
            ->withQuery(http_build_query($query))
            ->withFragment('');
    }
}
