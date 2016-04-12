<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTP;

interface CookieEncryptionInterface
{
    /**
     * @param string $unencrypted
     *
     * @return string
     */
    public function encrypt($unencrypted);

    /**
     * @param string $encrypted
     *
     * @return string|null
     */
    public function decrypt($encrypted);
}
