<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Utility;

use Psr\Http\Message\UriInterface;
use Slim\Router;

class Url
{
    /**
     * @type Router
     */
    private $router;

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
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
    public function getQueryParam(UriInterface $uri, $param)
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
    public function urlFor($route, array $params = [], array $query = [])
    {
        if (!$route) {
            return '';
        }

        return $this->router->relativePathFor($route, $params, $query);
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
    public function absoluteUrlFor(UriInterface $uri, $route, array $params = [], array $query = [])
    {
        $path = $this->urlFor($route, $params);

        return (string) $uri
            ->withUserInfo('')
            ->withPath($path)
            ->withQuery(http_build_query($query))
            ->withFragment('');
    }
}
