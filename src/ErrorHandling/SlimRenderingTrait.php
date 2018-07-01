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
     * @var string
     */
    private $fallbackErrorResponse = <<<ERROR_RESPONSE
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Panthor Error</title>
    </head>

    <body>
        <header>
            <h1>Panthor Error</h1>
        </header>
        <main>
            <p>
                Internal Server Error. The application failed to launch.
            </p>
        </main>
    </body>
</html>

ERROR_RESPONSE;

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
     * @param string $response
     *
     * @return void
     */
    public function setFallbackError(string $response = '')
    {
        $this->fallbackErrorResponse = $response;
    }

    /**
     * @param ResponseInterface $response
     */
    private function renderResponse(ResponseInterface $response)
    {
        if ($this->slim) {
            $this->slim->respond($response);
        } else {
            if ($this->fallbackErrorResponse) {
                echo $this->fallbackErrorResponse;
            }

            http_response_code(500);
        }
    }
}
