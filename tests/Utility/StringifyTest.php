<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Utility;

use PHPUnit_Framework_TestCase;

class StringifyTest extends PHPUnit_Framework_TestCase
{
    public function testTemplate()
    {
        $params = [
            'dev',
            'staging',
            'prod'
        ];

        $actual = Stringify::template('%s-%s/%s', $params);

        $this->assertSame('dev-staging/prod', $actual);
    }

    public function testCombine()
    {
        $params = [
            'dev',
            'staging',
            'prod'
        ];

        $actual = Stringify::combine($params);

        $this->assertSame('devstagingprod', $actual);
    }
}
