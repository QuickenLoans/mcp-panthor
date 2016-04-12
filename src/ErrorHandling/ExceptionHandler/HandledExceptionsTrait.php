<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ExceptionHandler;

use Exception;
use Throwable;

trait HandledExceptionsTrait
{
    /**
     * @type array
     */
    private $handledThrowables = [];

    /**
     * @param Throwable $throwable
     *
     * @return bool
     */
    public function isValidThrowable($throwable)
    {
        if ($throwable instanceof Exception || $throwable instanceof Throwable) {
            return true;
        }

        return false;
    }

    /**
     * @param Exception|Throwable $throwable
     *
     * @return bool
     */
    public function canHandleThrowable($throwable)
    {
        if (!$this->isValidThrowable($throwable)) {
            return false;
        }

        foreach ($this->handledThrowables as $throwableType) {
            if ($throwable instanceof $throwableType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $throwables
     *
     * @return void
     */
    public function setHandledThrowables(array $throwables)
    {
        $this->handledThrowables = $throwables;
    }
}
