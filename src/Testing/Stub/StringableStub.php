<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Testing\Stub;

class StringableStub
{
    /**
     * @var mixed
     */
    public $output;

    /**
     * @param string $output
     */
    public function __construct($output)
    {
        $this->output = $output;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->output;
    }
}
