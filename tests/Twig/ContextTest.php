<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Twig;

use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    public function testInitiallyLoadedContext()
    {
        $context = new Context([
            'test1' => 'value1',
            'test2' => ['test3' => 'value3']
        ]);

        $this->assertCount(2, $context);
    }

    public function testIterationOfContext()
    {
        $context = new Context([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ]);

        $i = 0;
        foreach ($context as $val) {
            $expected = 'value' . ++$i;
            $this->assertSame($expected, $val);
        }
    }

    public function testMergingContextPreservesChildren()
    {
        $context = new Context([
            'test1' => 'value1',
            'test2' => ['test3' => 'value3']
        ]);

        $context->addContext([
            'test2' => ['test4' => 'value4']
        ]);


        $expected = [
            'test3' => 'value3',
            'test4' => 'value4'
        ];

        $this->assertSame($expected, $context->get('test2'));
    }

    public function testGetScenarios()
    {
        $raw = [
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ];

        $context = new Context($raw);

        $this->assertSame('value2', $context->get('test2'));
        $this->assertSame(null, $context->get('test4'));
        $this->assertSame($raw, $context->get());
    }

    public function testMergeReplace()
    {
        $context = new Context([
            'test1' => 'value1',
            'test2' => ['test3' => 'value3']
        ]);

        $context->addContext([
            'test1' => 'value2',
            'test2' => ['test4' => 'value4']
        ]);

        $expected = [
            'test1' => 'value2',
            'test2' => [
                'test3' => 'value3',
                'test4' => 'value4'
            ]
        ];

        $this->assertSame($expected, $context->get());
    }

    /**
     * Ensure that self-referencing objects do not cause a segfault. This can happen when array_merge_recursive
     * is used to merge context.
     */
    public function testMergeSelfReferencingObjects()
    {
        $a = new \stdClass();

        $a->a = $a;

        $context = new Context([
            'a' => $a
        ]);

        $context->addContext([
            'a' => $a
        ]);

        $expected = [
            'a' => $a
        ];

        $this->assertSame($expected, $context->get());
    }
}
