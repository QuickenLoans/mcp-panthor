<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Middleware;

use QL\Panthor\Exception\RequestException;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\Json;
use Slim\Http\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sanitize the request properties and normalize for consumption by the application.
 *
 * This middleware will accept and normalize the following content types:
 * - application/x-www-form-urlencoded
 * - application/json
 * - multipart/form-data
 */
class RequestBodyMiddleware implements MiddlewareInterface
{
    const NOFUNZONE = 'peanutbutterjellytime';

    const ERR_UNSUPPORTED = 'Unsupported Media Type';
    const ERR_UNSUPPORTED_CODE = 415;

    const ERR_INVALID_CODE = 400;

    /**
     * @type ContainerInterface
     */
    private $di;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Json
     */
    private $json;

    /**
     * @type string
     */
    private $serviceName;

    /**
     * @type array|null
     */
    private $defaultKeys;

    /**
     * @param ContainerInterface $di
     * @param Request $request
     * @param Json $json
     * @param string $serviceName
     */
    public function __construct(ContainerInterface $di, Request $request, Json $json, $serviceName)
    {
        $this->di = $di;
        $this->request = $request;
        $this->json = $json;
        $this->serviceName = $serviceName;
    }

    /**
     * @throws RequestException
     *
     * @return void
     */
    public function __invoke()
    {
        $mediaType = $this->request->getMediaType();
        if ($mediaType === 'application/json') {
            $decoded = $this->handleJson();

        } elseif ($mediaType === 'application/x-www-form-urlencoded') {
            $decoded = $this->request->post();

        } elseif ($mediaType === 'multipart/form-data') {
            $decoded = $this->request->post();

        } else {
            throw new RequestException(static::ERR_UNSUPPORTED, static::ERR_UNSUPPORTED_CODE);
        }

        if ($this->defaultKeys) {
            $default = array_fill_keys($this->defaultKeys, null);
            $decoded = array_replace($default, $decoded);
        }

        // symfony can't set empty array :(
        if (count($decoded) === 0) {
            $decoded = [static::NOFUNZONE];
        }

        $this->di->set($this->serviceName, $decoded);
    }

    /**
     * Set default keys for the parsed body.
     *
     * This allows you to populate missing keys in the request, to avoid annoying isset() checks.
     *
     * Example:
     *
     * Request Body (JSON):
     * {
     *     "key1": "test1",
     *     "key2": "test2",
     *     "key4": "test4",
     * }
     *
     * $middleware->setDefaultKeys(['key1', 'key2', 'key3', 'key4', 'key5']);
     *
     * Parsed Request (PHP):
     * [
     *     'key1' => 'test1',
     *     'key2' => 'test2',
     *     'key3' => null,
     *     'key4' => 'test4',
     *     'key5' => null
     * ]
     *
     * @param array $keys
     *
     * @return void
     */
    public function setDefaultKeys(array $keys)
    {
        $this->defaultKeys = $keys;
    }

    /**
     * @throws RequestException
     *
     * @return array
     */
    protected function handleJson()
    {
        $body = $this->request->getBody();
        $decoded = call_user_func($this->json, $body);

        if (!is_array($decoded)) {
            throw new RequestException($decoded, static::ERR_INVALID_CODE);
        }

        return $decoded;
    }
}
