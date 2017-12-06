<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Session;

use JsonSerializable;

interface SessionInterface extends JsonSerializable
{
    /**
     * Stores a given value in the session.
     *
     * @param string                      $key
     * @param int|bool|string|float|array $value
     *
     * @return void
     */
    public function set($key, $value);

    /**
     * Retrieves a value from the session.
     *
     * @param string $key
     * @param int|bool|string|float|array $default
     *
     * @return int|bool|string|float|array
     */
    public function get($key, $default = null);

    /**
     * Removes an item from the session.
     *
     * @param string $key
     *
     * @return void
     */
    public function remove($key);

    /**
     * Clears the entire session.
     *
     * @return void
     */
    public function clear();

    /**
     * Checks whether a given key exists in the session.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * Checks whether the session has changed its contents.
     *
     * @return bool
     */
    public function hasChanged();

    /**
     * @param string $data
     *
     * @return SessionInterface|null
     */
    public static function fromSerialized($data);

    /**
     * @param SessionInterface $session
     *
     * @return string
     */
    public static function toSerialized(SessionInterface $session);
}
