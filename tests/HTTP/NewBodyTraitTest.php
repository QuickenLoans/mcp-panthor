<?php

namespace QL\Panthor\HTTP;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;

class NewBodyTraitTest extends TestCase
{
    public $dummy;
    public $response;

    public function setUp()
    {
        $this->response = (new ResponseFactory)->createResponse();

        $this->dummy = new class {
            use NewBodyTrait {
                withNewBody as public;
            }
        };

    }

    public function testSettingNewResponseBody()
    {
        $newBody = $this->dummy;
        $output = $newBody->withNewBody($this->response, "data data\nmore data");

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $output->getProtocolVersion();

        $expectedStatusCode = 200;
        $actualStatusCode = $output->getStatusCode();

        $expectedReasonPhrase = 'OK';
        $actualReasonPhrase = $output->getReasonPhrase();

        $expectedBody = <<<EOT
        data data
        more data
        EOT;

        $actualBody = $output->getBody();
        $actualBody->rewind();

        $this->assertSame($expectedHTTPVersion, $actualHTTPVersion);
        $this->assertSame($expectedStatusCode, $actualStatusCode);
        $this->assertSame($expectedReasonPhrase, $actualReasonPhrase);
        $this->assertSame($expectedBody, $actualBody->getContents());
    }
}
