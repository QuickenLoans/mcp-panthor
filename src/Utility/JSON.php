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
     * @type callable|string
     */
    private $jsonMessageFunction;

    /**
     * @type int
     */
    private $encodingOptions;

    /**
     * @param callable|string $jsonMessageFunction
     */
    public function __construct($jsonMessageFunction = 'json_last_error_msg')
    {
        $this->jsonMessageFunction = $jsonMessageFunction;

        $this->encodingOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
    }

    /**
     * @type array
     */
    private static $jsonErrors = [
        JSON_ERROR_NONE => 'No error',
        JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
        JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
        JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
        JSON_ERROR_SYNTAX => 'Syntax error',
        JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    ];

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
            return sprintf('Invalid json (%s)', $this->lastJsonErrorMessage());
        }

        return $decoded;
    }

    /**
     * @return string
     */
    public function lastJsonErrorMessage()
    {
        if (is_callable($this->jsonMessageFunction)) {
            return call_user_func($this->jsonMessageFunction);
        }

        $error = json_last_error();
        if (isset(self::$jsonErrors[$error])) {
            return self::$jsonErrors[$error];
        }

        return $error;
    }

    /**
     * @param string $json
     *
     * @return mixed|null
     */
    public function decode($json)
    {
        $decoded = json_decode($json, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
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
    public function setEncodingOptions($encodingOptions)
    {
        $this->encodingOptions = (int) $encodingOptions;
    }
    /**
     * @see http://php.net/manual/en/json.constants.php
     *
     * @param int $encodingOptions
     *
     * @return void
     */
    public function addEncodingOptions($encodingOptions)
    {
        $this->encodingOptions = $this->encodingOptions | (int) $encodingOptions;
    }
}
