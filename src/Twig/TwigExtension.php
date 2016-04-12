<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Twig;

use DateTime;
use DateTimeZone;
use QL\MCP\Common\Time\Clock;
use QL\MCP\Common\Time\TimePoint;
use QL\Panthor\Utility\Url;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

class TwigExtension extends Twig_Extension
{
    /**
     * @type Url
     */
    private $url;

    /**
     * @type Clock
     */
    private $clock;

    /**
     * @type string
     */
    private $displayTimezone;

    /**
     * @type bool
     */
    private $isDebugMode;

    /**
     * @param Url $url
     * @param Clock $clock
     * @param string $timezone
     * @param bool $isDebugMode
     */
    public function __construct(Url $url, Clock $clock, $timezone, $isDebugMode)
    {
        $this->url = $url;
        $this->clock = $clock;

        $this->displayTimezone = $timezone;
        $this->isDebugMode = (bool) $isDebugMode;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'panthor';
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('timepoint', [$this, 'formatTimePoint']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('urlFor', [$this->url, 'urlFor']),

            new Twig_SimpleFunction('isDebugMode', [$this, 'isDebugMode']),

            new Twig_SimpleFunction('timepoint', [$this, 'getTimepoint'])
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
