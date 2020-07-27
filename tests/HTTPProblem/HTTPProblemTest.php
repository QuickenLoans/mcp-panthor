<?php

namespace QL\Panthor\HTTPProblem;

use PHPUnit\Framework\TestCase;

class HTTPProblemTest extends TestCase
{
    public function testMinimumValidProblem()
    {
        $status = 418;
        $detail = 'A detailed message specific to this problem, but not unique.';

        $problem = new HTTPProblem($status, $detail);

        $this->assertSame(418, $problem->status());
        $this->assertSame('A detailed message specific to this problem, but not unique.', $problem->detail());
        $this->assertSame([], $problem->extensions());

        // Title autodetermined from status if not provided.
        $this->assertSame("I'm a teapot", $problem->title());
        $this->assertSame(null, $problem->type());
        $this->assertSame(null, $problem->instance());
    }

    public function testInvalidStatusDefaultsTo500()
    {
        $status = 9999;

        $problem = new HTTPProblem($status, 'msg');

        $this->assertSame(500, $problem->status());

        // Title autodetermined from status if not provided.
        $this->assertSame('Internal Server Error', $problem->title());
    }

    public function testTitleNotAutodeterminedIfCustomTypeUsed()
    {
        $problem = new HTTPProblem(400, 'msg');
        $problem->withType('http://example.com');

        $this->assertSame(null, $problem->title());
    }

    public function testInvalidStatusReturnsUnknownTitle()
    {
        $problem = new HTTPProblem(399, 'msg');

        $this->assertSame('Unknown', $problem->title());
    }

    public function testInstanceAndTypeRequireValidURLs()
    {
        $problem = new HTTPProblem(400, 'msg');
        $problem
            ->withType('not-a-url')
            ->withInstance('not-a-url-2');

        $this->assertSame(null, $problem->type());
        $this->assertSame(null, $problem->instance());
    }

    public function testAboutBlankIsValidURLForType()
    {
        $problem = new HTTPProblem(400, 'msg');
        $problem
            ->withType('about:blank')
            ->withInstance('about:blank');

        $this->assertSame('about:blank', $problem->type());
        $this->assertSame(null, $problem->instance());
    }

    public function testURLsSetToNullIfInvalid()
    {
        $problem = new HTTPProblem(400, 'msg');
        $problem
            ->withType('http://example.com')
            ->withInstance('http://example.com/page.html');

        $this->assertSame('http://example.com', $problem->type());
        $this->assertSame('http://example.com/page.html', $problem->instance());

        $problem
            ->withType('not-a-url')
            ->withInstance('not-a-url-2');

        $this->assertSame(null, $problem->type());
        $this->assertSame(null, $problem->instance());
    }

    public function testExtensionsAdded()
    {
        $problem = new HTTPProblem(400, 'msg');
        $problem->withExtensions([
            'data1' => '1234',
            'data2' => 'abcd'
        ]);

        $expected = [
            'data1' => '1234',
            'data2' => 'abcd'
        ];

        $this->assertSame($expected, $problem->extensions());
    }

    public function testExtensionsAlwaysOverwritten()
    {
        $ext1 = [
            'defdata' => 'fghi'
        ];

        $ext2 = [
            'data1' => '1234',
            'data2' => 'abcd'
        ];

        $problem = new HTTPProblem(400, 'msg', $ext1);

        $this->assertSame($ext1, $problem->extensions());

        $problem->withExtensions($ext2);

        $this->assertSame($ext2, $problem->extensions());
    }
}
