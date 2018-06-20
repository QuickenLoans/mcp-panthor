<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Session;

use Mockery;
use PHPUnit\Framework\TestCase;

class JSONEncodedSessionTest extends TestCase
{
    public function testAccessors()
    {
        $data = [
            'integer' => 123,
            'string' => 'test value',
            'list' => [
                '123',
                'abc',
                '456'
            ],
            'boolean' => false
        ];

        $session = new JSONEncodedSession($data);

        $this->assertSame(true, $session->has('list'));
        $this->assertSame(true, $session->has('boolean'));

        $this->assertSame(['123', 'abc', '456'], $session->get('list'));
        $this->assertSame(false, $session->get('boolean'));

        $this->assertSame(false, $session->hasChanged());
    }

    public function testGetWithDefaultValues()
    {
        $data = [
            'ghi' => 'c3'
        ];
        $session = new JSONEncodedSession($data);

        $this->assertSame('a1', $session->get('abc', 'a1'));
        $this->assertSame('b2', $session->get('def', 'b2'));
        $this->assertSame('c3', $session->get('ghi', 'c4'));
    }

    public function testRemovalAndClear()
    {
        $data = [
            'integer' => 123,
            'string' => 'test value',
            'list' => [
                '123',
                'abc',
                '456'
            ],
            'boolean' => false
        ];

        $session = new JSONEncodedSession($data);

        $this->assertSame(true, $session->has('string'));

        $session->remove('string');
        $this->assertSame(false, $session->has('string'));

        $this->assertSame(true, $session->has('list'));
        $session->clear();
        $this->assertSame(false, $session->has('list'));
    }

    public function testChanges()
    {
        $data = [
            'integer' => 123,
            'string' => 'test value',
            'list' => [
                '123',
                'abc',
                '456'
            ],
            'boolean' => false
        ];

        $session = new JSONEncodedSession($data);

        $this->assertSame(false, $session->hasChanged());

        $session->set('integer', 123);
        $this->assertSame(false, $session->hasChanged());

        $session->set('integer', 456);
        $this->assertSame(true, $session->hasChanged());
    }

    public function testSerialization()
    {
        $data = [
            'integer' => 123,
            'string' => 'test value',
            'list' => [
                '123',
                'abc',
                '456'
            ],
            'boolean' => false
        ];

        $expected = '{"integer":123,"string":"test value","list":["123","abc","456"],"boolean":false}';

        $session = new JSONEncodedSession($data);
        $serialized = JSONEncodedSession::toSerialized($session);
        $this->assertSame($expected, $serialized);

        $sessionDeserialized = JSONEncodedSession::fromSerialized($serialized);
        $this->assertSame(['123', 'abc', '456'], $session->get('list'));
        $this->assertSame(false, $session->get('boolean'));
        $this->assertSame(123, $session->get('integer'));
    }
}
