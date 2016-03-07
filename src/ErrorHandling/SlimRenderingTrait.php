<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling;

use Psr\Http\Message\ResponseInterface;
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
     * @param ResponseInterface $response
     */
    private function renderResponse(ResponseInterface $response)
    {
        if ($this->slim) {
            $this->slim->respond($response);
        }

        // do not set body for HEAD requests
        if ($this->slim && $this->slim->request->getMethod() == 'head') {
            return;
        }

        echo $response->getBody();
    }
}
