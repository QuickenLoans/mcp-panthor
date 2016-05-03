<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTP;

use Mockery;
use PHPUnit_Framework_TestCase;
use Slim\Http\Response;

class NewBodyTraitTest extends PHPUnit_Framework_TestCase
{
    public $response;

    public function setUp()
    {
        $this->response = new Response;
    }

    public function testSettingNewResponseBody()
    {
        $newBody = new NewBodyTraitStub;
        $output = $newBody->withNewBody($this->response, "data data\nmore data");
        $expected = <<<'HTTP'
HTTP/1.1 200 OK

data data
more data
HTTP;

        $this->assertSame($expected, (string) $output);

    }
}

class NewBodyTraitStub
{
    use NewBodyTrait {
        withNewBody as public;
    }
}
