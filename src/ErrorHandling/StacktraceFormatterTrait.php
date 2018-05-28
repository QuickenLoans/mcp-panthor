<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling;

use Exception;
use Throwable;

trait StacktraceFormatterTrait
{
    /**
     * @var string
     */
    private $root;

    /**
     * @var bool
     */
    private $logStacktraces = false;

    /**
     * @param bool $enableLogging
     *
     * @return void
     */
    public function setStacktraceLogging($enableLogging)
    {
        $this->logStacktraces = (bool) $enableLogging;
    }

    /**
     * Unpack nested throwables into a flat array
     *
     * @param Throwable|Exception $parent
     *
     * @return Throwable[]|Exception[]
     */
    private function unpackThrowables($throwable)
    {
        if (!$throwable instanceof Throwable && !$throwable instanceof Exception) {
            return [];
        }

        $throwables = [$throwable];
        while ($throwable = $throwable->getPrevious()) {
            $throwables[] = $throwable;
        }

        return $throwables;
    }

    /**
     * @param array $stacktrace
     *
     * @return string
     */
    private function formatStacktrace(array $stacktrace)
    {
        $this->root = $this->findApplicationRoot();

        $trace = '';
        if ($first = array_shift($stacktrace)) {
            $trace = $this->formatStacktraceEntry('ERR', $first);
        }

        if (!$this->logStacktraces) {
            return $trace;
        }

        foreach ($stacktrace as $index => $entry) {
            $trace .= $this->formatStacktraceEntry(sprintf('#%d', $index), $entry);
        }

        return $trace;
    }

    /**
     * @param Exception|Exception[] $exceptions
     *
     * @return string
     */
    private function formatStacktraceForExceptions($exceptions)
    {
        if (!is_array($exceptions)) {
            $exceptions = [$exceptions];
        }

        $this->root = $this->findApplicationRoot();

        $trace = '';
        foreach ($exceptions as $ex) {
            if ($ex instanceof Exception || $ex instanceof Throwable) {
                $trace .= $this->formatExceptionStacktrace($ex);
            }
        }

        return $trace;
    }

    /**
     * @param Exception|Throwable $exception
     *
     * @return string
     */
    private function formatExceptionStacktrace($exception)
    {
        if (!$exception instanceof Exception && !$exception instanceof Throwable) {
            return '';
        }

        $trace = $this->formatStacktraceEntry('ERR', [
            'function' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);

        if (!$this->logStacktraces) {
            return $trace;
        }

        foreach ($exception->getTrace() as $index => $entry) {
            $trace .= $this->formatStacktraceEntry(sprintf('#%d', $index), $entry);
        }

        return $trace;
    }

    /**
     * @param string $index
     * @param array $entry
     *
     * @return string
     */
    private function formatStacktraceEntry($index, array $entry)
    {
        $entry = array_replace([
            'file' => '',
            'line' => '?',
            'function' => '',
            'type' => '',
            'class' => '',
            'args' => [],
        ], $entry);

        if ($entry['class']) {
            $function = $entry['class'] . $entry['type'] . $entry['function'];
            $args = $entry['args'];
            array_walk($args, function (&$v) {
                $v = is_object($v) ? get_class($v) : gettype($v);
            });
            $function .= sprintf('(%s)', implode(', ', $args));
        } else {
            $function = $entry['function'];
        }

        $label = str_pad($index, 3);

        $file = $entry['file'] ? sprintf('%s:%s', $entry['file'], $entry['line']) : '[internal function]';
        $file = ($this->root) ? str_replace($this->root, '', $file) : $file;

        $entry = <<<TEXT
$label $file
    $function
TEXT;

        return $entry . str_repeat(PHP_EOL, 2);
    }

    /**
     * @return string
     */
    private function findApplicationRoot()
    {
        $current = __DIR__;
        if ($cut = strpos($current, 'vendor')) {
            return substr($current, 0, $cut);
        }

        return '';
    }
}
