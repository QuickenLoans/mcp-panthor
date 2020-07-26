<?php

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
