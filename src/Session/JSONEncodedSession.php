<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Session;

use JsonSerializable;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_UNESCAPED_SLASHES;

class JSONEncodedSession implements SessionInterface
{
    /**
     * @var array
     */
    private $data;
    private $original;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $this->original = $data;
    }

    /**
     * @param string $data
     *
     * @return SessionInterface|null
     */
    public static function fromSerialized(string $data): ?SessionInterface
    {
        if (!$data || !is_string($data)) {
            return null;
        }

        $decoded = json_decode($data, true);

        if (!is_array($decoded)) {
            return null;
        }

        return new self($decoded);
    }

    /**
     * @param SessionInterface $session
     *
     * @return string
     */
    public static function toSerialized(SessionInterface $session): string
    {
        return json_encode($session, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * Stores a given value in the session.
     *
     * @param string                      $key
     * @param int|bool|string|float|array $value
     *
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->data[$key] = self::convertValueToScalar($value);
    }

    /**
     * Retrieves a value from the session.
     *
     * @param string $key
     * @param int|bool|string|float|array $default
     *
     * @return int|bool|string|float|array
     */
    public function get(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return self::convertValueToScalar($default);
        }

        return $this->data[$key];
    }

    /**
     * Removes an item from the session.
     *
     * @param string $key
     *
     * @return void
     */
    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Clears the entire session.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->data = [];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Checks whether the session has changed its contents.
     *
     * @return bool
     */
    public function hasChanged(): bool
    {
        return $this->data !== $this->original;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * @param int|bool|string|float|array|object|JsonSerializable $value
     *
     * @return int|bool|string|float|array
     */
    private static function convertValueToScalar($value)
    {
        return json_decode(json_encode($value, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION), true);
    }
}
