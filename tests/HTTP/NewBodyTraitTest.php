<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTP;

use Mockery;
use PHPUnit\Framework\TestCase;
use Slim\Http\Response;

class NewBodyTraitTest extends TestCase
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

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $output->getProtocolVersion();

        $expectedStatusCode = 200;
        $actualStatusCode = $output->getStatusCode();

        $expectedReasonPhrase = 'OK';
        $actualReasonPhrase = $output->getReasonPhrase();

        $expectedBody = <<<'HTTP'
data data
more data
HTTP;
        $actualBody = $output->getBody();
        $actualBody->rewind();

        $this->assertSame($expectedHTTPVersion, $actualHTTPVersion);
        $this->assertSame($expectedStatusCode, $actualStatusCode);
        $this->assertSame($expectedReasonPhrase, $actualReasonPhrase);
        $this->assertSame($expectedBody, $actualBody->getContents());
    }
}

class NewBodyTraitStub
{
    use NewBodyTrait {
        withNewBody as public;
    }
}
