<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\App;

/**
 * Force sending of the response and end the php process.
 *
 * This is copypasta from Slim\Slim::run, as once an error occurs and the application has broken out of Slim's
 * handling context, Slim cannot be made to re-render the response.
 */
trait SlimRenderingTrait
{
    /**
     * @type App|null
     */
    private $slim;

    /**
     * @type callable|null
     */
    private $headerSetter;

    /**
     * @param App $slim
     * @param callable|null $headerSetter
     *
     * @return void
     */
    public function attachSlim(App $slim, callable $headerSetter = null)
    {
        $this->slim = $slim;
        $this->headerSetter = $headerSetter;
    }

    /**
     * @param int $status
     * @param string $body
     * @param string[] $additionalHeaders
     *
     * @return void
     */
    private function renderResponse(
        Request $request,
        Response $response,
        $status = 500,
        $body = '',
        array $additionalHeaders = []
    ) {
        $httpVersion = '1.1';
        $httpStatus = $response->getReasonPhrase()?: 500;

        $setHeader = is_callable($this->headerSetter) ? $this->headerSetter : '\header';

        if ($this->slim) {
            $response = $response->write($body)->withStatus($status);

            foreach ($additionalHeaders as $key => $value) {
                $response->headers->set($key, $value);
            }

            $httpStatus = $response->getReasonPhrase();
            $httpHeaders = $response->getHeaders();
            $body = $response->getBody()->getContents();
            $httpVersion = $this->slim->getContainer()->get('http.version');

        } else {
            $httpHeaders = $additionalHeaders;
            http_response_code($status);
        }

        if (headers_sent() === false) {

            // Send status
            $setHeader(sprintf('HTTP/%s %s', $httpVersion, $httpStatus));

            // Send headers
            foreach ($httpHeaders as $name => $value) {
                $hValues = explode("\n", $value);
                foreach ($hValues as $hVal) {
                    $setHeader(sprintf('%s: %s', $name, $hVal), false);
                }
            }
        }

        // do not set body for HEAD requests
        if ($this->slim && $this->slim->request()->isHead()) {
            return;
        }

        echo $body;
    }
}
