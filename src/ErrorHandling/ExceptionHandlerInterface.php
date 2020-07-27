<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling;

use Exception;
use Throwable;

interface ExceptionHandlerInterface
{
    /**
     * Handle a throwable, and return whether it was handled and the remaining stack should be aborted.
     *
     * @param Exception|Throwable $throwable
     *
     * @return bool
     */
    public function handle($throwable);
}
