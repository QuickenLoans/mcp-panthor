<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Utility;

use PHPUnit\Framework\TestCase;
use QL\Panthor\Exception\Exception;

class ClosureFactoryTest extends TestCase
{
    public function testNonCallableThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid callable provided.');

        ClosureFactory::buildClosure('that', 'this');
    }

    public function testClosureIsCreated()
    {
        $closure = ClosureFactory::buildClosure($this, 'callACallable');

        $this->assertSame('taco tuesday', $closure());
    }

    public function callACallable()
    {
        return 'taco tuesday';
    }
}
