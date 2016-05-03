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
 * Render a PSR-7 response through slim.
 */
trait SlimRenderingTrait
{
    /**
     * @var App|null
     */
    private $slim;

    /**
     * @param App $slim
     *
     * @return void
     */
    public function attachSlim(App $slim)
    {
        $this->slim = $slim;
    }

    /**
     * @param ResponseInterface $response
     */
    private function renderResponse(ResponseInterface $response)
    {
        if ($this->slim) {
            $this->slim->respond($response);
        } else {
            // do something
        }
    }
}
