<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Twig;

use DateTime;
use DateTimeZone;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use QL\MCP\Common\Clock;
use QL\MCP\Common\Time\TimePoint;
use QL\Panthor\Utility\URI;
use stdClass;
use QL\Panthor\Testing\MockeryAssistantTrait;

class TwigExtensionTest extends TestCase
{
    use MockeryAssistantTrait;
    use MockeryPHPUnitIntegration;

    public $uri;
    public $clock;

    public function setUp()
    {
        $this->uri = Mockery::mock(URI::class);
        $this->clock = Mockery::mock(Clock::class);
    }

    public function testName()
    {
        $ext = new TwigExtension($this->uri, $this->clock, 'America\Detroit', false);
        $this->assertSame('panthor', $ext->getName());
    }

    public function testIsDebugMode()
    {
        $ext = new TwigExtension($this->uri, $this->clock, 'America\Detroit', true);
        $this->assertSame(true, $ext->isDebugMode());
    }

    public function testGetFunctionsDoesNotBlowUp()
    {
        $ext = new TwigExtension($this->uri, $this->clock, 'America\Detroit', false);
        $this->assertInternalType('array', $ext->getFunctions());
    }

    public function testGetFiltersDoesNotBlowUp()
    {
        $ext = new TwigExtension($this->uri, $this->clock, 'America\Detroit', false);
        $this->assertInternalType('array', $ext->getFilters());
    }

    public function testGetTimepointReadsFromClock()
    {
        $time = Mockery::mock(TimePoint::class);
        $this->clock
            ->shouldReceive('read')
            ->andReturn($time);

        $ext = new TwigExtension($this->uri, $this->clock, 'America\Detroit', false);
        $this->assertSame($time, $ext->getTimepoint());
    }

    public function testGetTimepointReadsFromClockAndModifies()
    {
        $time = Mockery::mock(TimePoint::class);
        $time
            ->shouldReceive('modify')
            ->with('+2 days')
            ->once();

        $this->clock
            ->shouldReceive('read')
            ->andReturn($time);

        $ext = new TwigExtension($this->uri, $this->clock, 'America\Detroit', false);
        $this->assertSame($time, $ext->getTimepoint('+2 days'));
    }

    public function testFormattingDateAcceptsDateTime()
    {
        $expected = '2014-08-05 11:00:32';
        $datetime = new DateTime('2014-08-05 15:00:32', new DateTimeZone('UTC'));
        $ext = new TwigExtension($this->uri, $this->clock, 'America/Detroit', false);

        $this->assertSame($expected, $ext->formatTimepoint($datetime, 'Y-m-d H:i:s'));
    }

    public function testFormattingDateAcceptsTimePoint()
    {
        $expected = '2014-08-05 15:00:32';
        $timepoint = new TimePoint(2014, 8, 5, 15, 0, 32, 'America/Detroit');
        $ext = new TwigExtension($this->uri, $this->clock, 'America/Detroit', false);

        $this->assertSame($expected, $ext->formatTimepoint($timepoint, 'Y-m-d H:i:s'));
    }

    public function testFormattingDateFailsGracefullyWithUnknownType()
    {
        $invalid = new stdClass;
        $ext = new TwigExtension($this->uri, $this->clock, 'America/Detroit', false);

        $this->assertSame('', $ext->formatTimepoint($invalid, 'Y-m-d H:i:s'));
    }
}
