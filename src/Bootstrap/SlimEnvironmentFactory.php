<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use Slim\Http\Environment;

class SlimEnvironmentFactory
{
    /**
     * @return Environment
     */
    public static function fromGlobal()
    {
        return new Environment($_SERVER);
    }
}
