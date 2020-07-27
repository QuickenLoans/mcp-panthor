<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Utility;

use JsonException;
use JsonSerializable;
use const JSON_THROW_ON_ERROR;

/**
 * Note that this is a simple trait for use that always returns a valid response in encoding/decoding
 * of JSON. Useful when you want to handle fault-tolerant caching (and suppress errors from invalid JSON).
 *
 * Use this for encoding/decoding JSON objects only. If you really really want to detect and/or know the
 * actual error, you can fetch it from `$json->lastJSONError();`
 */
trait JSONTrait
{
    private $lastJSONErrorMessage = '';

    /**
     * @return string
     */
    private function lastJSONError(): string
    {
        return $this->lastJSONErrorMessage;
    }

    /**
     * @param JsonSerializable|array $data
     * @param int $options
     *
     * @return string
     */
    private function encodeJSON($data, int $options = 0): string
    {
        $this->lastJSONErrorMessage = '';

        try {
            $encoded = json_encode($data, JSON_THROW_ON_ERROR | $options);
        } catch (JsonException $ex) {
            $this->lastJSONErrorMessage = $ex->getMessage();
            $encoded = '[]';
        }

        return $encoded;
    }

    /**
     * @param string $data
     * @param int $options
     *
     * @return array
     */
    private function decodeJSON(string $data, int $options = 0): array
    {
        $this->lastJSONErrorMessage = '';

        try {
            $decoded = json_decode($data, true, 128, JSON_THROW_ON_ERROR | $options);
        } catch (JsonException $ex) {
            $this->lastJSONErrorMessage = $ex->getMessage();
            $decoded = [];
        }

        if (!is_array($decoded)) {
            $decoded = [];
        }

        return $decoded;
    }
}
