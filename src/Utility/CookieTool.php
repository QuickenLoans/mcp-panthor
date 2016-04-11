<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Utility;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Cookies;

class CookieTool
{
    /**
     * Gets cookies from the response.
     *
     * @param ResponseInterface $response
     *
     * @return Cookies
     */
    public function getCookies(ResponseInterface $response)
    {
        return new Cookies($this->getRawCookies($response));
    }

    /**
     * Gets cokies in array format from the response.
     *
     * @param ResponseInterface $response
     *
     * @return array
     */
    public function getRawCookies(ResponseInterface $response)
    {
        return $this->parseHeaders($response->getHeader('Set-Cookie'));
    }

    /**
     * Returns the response with cookies applied.
     *
     * @param ResponseInterface $response
     * @param Cookies $cookies
     *
     * @return \Psr\Http\Message\MessageInterface
     */
    public function setCookies(ResponseInterface $response, Cookies $cookies)
    {
        $rawCookies = $this->getRawCookies($response);
        $parsedCookies = $this->parseHeaders($cookies->toHeaders());
        $builtCookies = $this->loadCookies($this->mergeCookies($rawCookies, $parsedCookies));
        return $response->withHeader('Set-Cookie', $builtCookies->toHeaders());
    }

    /**
     * Makes sure previously set and newly set cookies make their way to the final response.
     *
     * @param array $rawCookies
     * @param array $parsedCookies
     *
     * @return array
     */
    private function mergeCookies($rawCookies, $parsedCookies)
    {
        $rawKeys = array_keys($rawCookies);
        $parsedKeys = array_keys($parsedCookies);
        $keep = array_diff($parsedKeys, array_diff($rawKeys, array_intersect($rawKeys, $parsedKeys)));
        $builtCookies = array_merge($rawCookies, $parsedCookies);
        array_walk($builtCookies, function (&$value, $key) use ($keep) {
            if (!in_array($key, $keep)) {
                $value = '';
            }
        });
        return $builtCookies;
    }

    /**
     * Place raw cookies in a usable place in the cookies object... sigh.
     *
     * @param $rawCookies
     *
     * @return Cookies
     */
    private function loadCookies($rawCookies)
    {
        $cookies = new Cookies();
        foreach ($rawCookies as $key => $value) {
            $cookies->set($key, $value);
        }
        return $cookies;
    }

    /**
     * @param array $headers
     * @param array $parsedHeaders
     *
     * @return array
     */
    private function parseHeaders($headers, $parsedHeaders = [])
    {
        foreach ($headers as $key => $header) {
            if (is_string($key)) {
                $parsedHeaders[$key] = $header;
            } else {
                $parsedHeader = Cookies::parseHeader($header);
                $parsedHeaders = array_merge($parsedHeaders, $parsedHeader);
            }
        }
        return array_filter($parsedHeaders, function($header) {return !empty($header);});
    }
}
