<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Utility;

use Closure;
use QL\Panthor\Exception\Exception;

class ClosureFactory
{
    /**
     * @param object $service
     * @param string $method
     *
     * @return Closure
     */
    public static function buildClosure($service, $method)
    {
        $callable = [$service, $method];
        if (!is_callable($callable)) {
            throw new Exception('Invalid callable provided.');
        }

        return function() use ($callable) {
            return call_user_func_array($callable, func_get_args());
        };
    }
}
