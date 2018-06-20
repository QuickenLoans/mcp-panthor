<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Templating;

use PHPUnit\Framework\TestCase;

class NullTemplateTest extends TestCase
{
    public function testNullTemplateRendersEmptyString()
    {
        $template = new NullTemplate;

        $rendered = $template->render([
            'param1' => 'abcd',
            'param2' => '1234',
        ]);

        $this->assertSame('', $rendered);
    }
}
