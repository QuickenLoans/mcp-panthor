<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Twig;

use DateTime;
use DateTimeZone;
use QL\MCP\Common\Clock;
use QL\MCP\Common\Time\TimePoint;
use QL\Panthor\Utility\URI;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    /**
     * @var URI
     */
    private $uri;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var string
     */
    private $displayTimezone;

    /**
     * @var bool
     */
    private $isDebugMode;

    /**
     * @param URI $uri
     * @param Clock $clock
     * @param string $timezone
     * @param bool $isDebugMode
     */
    public function __construct(URI $uri, Clock $clock, $timezone, $isDebugMode)
    {
        $this->uri = $uri;
        $this->clock = $clock;

        $this->displayTimezone = $timezone;
        $this->isDebugMode = (bool) $isDebugMode;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'panthor';
    }

    /**
     * @inheritDoc
     */
    public function getFilters()
    {
        return [
            new TwigFilter('timepoint', [$this, 'formatTimePoint']),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('uriFor', [$this->uri, 'uriFor']),

            new TwigFunction('isDebugMode', [$this, 'isDebugMode']),

            new TwigFunction('timepoint', [$this, 'getTimepoint'])
        ];
    }

    /**
     * @return bool
     */
    public function isDebugMode()
    {
        return ($this->isDebugMode);
    }

    /**
     * @param string|null $modifier
     *
     * @return TimePoint
     */
    public function getTimepoint($modifier = null)
    {
        $now = $this->clock->read();
        if ($modifier) {
            $now->modify($modifier);
        }

        return $now;
    }

    /**
     * Format a DateTime or TimePoint. Invalid values will output an empty string.
     *
     * @param TimePoint|DateTime|null $time
     * @param string $format
     *
     * @return string
     */
    public function formatTimepoint($time, $format)
    {
        if ($time instanceof TimePoint) {
            return $time->format($format, $this->displayTimezone);
        }

        if ($time instanceof DateTime) {
            $formatted = clone $time;
            $formatted->setTimezone(new DateTimeZone($this->displayTimezone));
            return $formatted->format($format);
        }

        return '';
    }
}
