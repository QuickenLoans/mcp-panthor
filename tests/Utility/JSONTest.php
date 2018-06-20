<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Utility;

use PHPUnit\Framework\TestCase;
use stdClass;

class JSONTest extends TestCase
{
    public function testEncoding()
    {
        $input = new stdClass;
        $input->test1 = 'abcd1';
        $input->test2 = 'abcd2';

        $expected = <<<FORMATTED
{"test1":"abcd1","test2":"abcd2"}
FORMATTED;

        $json = new JSON;
        $output = $json->encode($input);

        $this->assertSame($expected, $output);
    }

    public function testEncodingWithCustomEncodingOption()
    {
        $input = new stdClass;
        $input->test1 = 'abcd1';
        $input->test2 = 'abcd2';

        $expected = <<<FORMATTED
{
    "test1": "abcd1",
    "test2": "abcd2"
}
FORMATTED;

        $json = new JSON;
        $json->setEncodingOptions(JSON_PRETTY_PRINT);
        $output = $json->encode($input);

        $this->assertSame($expected, $output);
    }

    public function testDecoding()
    {
        $input = <<<FORMATTED
{
    "test1": "abcd1",
    "test2": "abcd2"
}
FORMATTED;

        $expected = [
            'test1' => 'abcd1',
            'test2' => 'abcd2'
        ];

        $json = new JSON;
        $json->setEncodingOptions(JSON_PRETTY_PRINT);
        $output = $json->decode($input);

        $this->assertSame($expected, $output);
    }

    public function testErrorWhileDecoding()
    {
        $input = <<<FORMATTED
{
    "test1"::"abcd1",
    "test2"::"abcd2"
}
FORMATTED;

        $expected = 'Invalid json (Syntax error)';

        $json = new JSON;

        $output = $json->decode($input);
        $this->assertSame(null, $output);

        $this->assertSame($expected, $json($input));
    }
}
