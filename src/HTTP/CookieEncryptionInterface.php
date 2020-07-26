<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTP;

interface CookieEncryptionInterface
{
    /**
     * @param string $unencrypted
     *
     * @return string|null
     */
    public function encrypt($unencrypted): ?string;

    /**
     * @param string $encrypted
     *
     * @return string|null
     */
    public function decrypt($encrypted): ?string;
}
