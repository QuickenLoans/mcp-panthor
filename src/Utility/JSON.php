<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Utility;

use JsonSerializable;

class JSON
{
    /**
     * @var int
     */
    private $encodingOptions;

    public function __construct()
    {
        $this->encodingOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
    }

    /**
     * Convenience method to decode json to an array, or return an error string on failure.
     *
     * @param string $json
     *
     * @return array|string
     */
    public function __invoke($json)
    {
        $decoded = $this->decode($json);
        if ($decoded === null || !is_array($decoded)) {
            return sprintf('Invalid json (%s)', json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * @return string
     */
    public function lastJsonErrorMessage()
    {
        return json_last_error_msg();
    }

    /**
     * @param string $json
     *
     * @return mixed|null
     */
    public function decode($json)
    {
        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param JsonSerializable|mixed $data
     *
     * @return string
     */
    public function encode($data)
    {
        return json_encode($data, $this->encodingOptions);
    }

    /**
     * @see http://php.net/manual/en/json.constants.php
     *
     * @param int $encodingOptions
     *
     * @return void
     */
    public function setEncodingOptions(int $encodingOptions)
    {
        $this->encodingOptions = $encodingOptions;
    }
    /**
     * @see http://php.net/manual/en/json.constants.php
     *
     * @param int $encodingOptions
     *
     * @return void
     */
    public function addEncodingOptions(int $encodingOptions)
    {
        $this->encodingOptions = $this->encodingOptions | $encodingOptions;
    }
}
