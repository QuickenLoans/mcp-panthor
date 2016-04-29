<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Testing;

use Mockery;

class Spy
{
    /**
     * @var mixed
     */
    private $captured;

    /**
     * Invoke this class with a value to store a value. To retrieve it, invoke the class with no arguments.
     *
     * @param mixed|null $captured
     *
     * @return mixed|null
     */
    public function __invoke($captured = null)
    {
        if (func_num_args() === 0) {
            return $this->captured;
        }

        $this->captured = $captured;
    }
}
