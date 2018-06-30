<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Testing;

use InvalidArgumentException;

/**
 * @codeCoverageIgnore
 */
trait FixtureLoadingTrait
{
    /**
     * Load a fixture as raw text.
     *
     * @param string $relativePath
     *
     * @return string
     */
    protected function loadRawFixture($relativePath)
    {
        $filePath = $this->getPath($relativePath);
        return file_get_contents($filePath);
    }

    /**
     * Load a fixture and evaluate as PHP code.
     *
     * @param string $relativePath
     *
     * @return string
     */
    protected function loadPhpFixture($relativePath)
    {
        $file = $this->loadRawFixture($relativePath);

        $code = 'return ' . $file . ';';
        return eval($code);
    }

    /**
     * Get the full path where fixtures are located. NO TRAILING SLASH.
     *
     * @param string|null $basePath
     *
     * @return string
     */
    protected function getFixturePath($basePath = null)
    {
        $path = $this->getTargetedClass();

        $basePath = $this->getBasePath($basePath);
        array_unshift($path, rtrim($basePath, DIRECTORY_SEPARATOR));

        return implode(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @param string $basePath
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    private function getBasePath($basePath)
    {
        if ($basePath === null) {
            if (defined('static::FIXTURES_DIR')) {
                return static::FIXTURES_DIR;
            }

            if (defined('FIXTURES_DIR')) {
                return FIXTURES_DIR;
            }

            throw new InvalidArgumentException('No base path found. Please provide one or define a global or class constant "FIXTURES_DIR"');
        }

        return $basePath;
    }

    /**
     * @return string[]
     */
    private function getTargetedClass()
    {
        $calledTest = get_called_class();
        $class = substr($calledTest, 0, -4);

        if (defined('NAMESPACE_PREFIX')) {
            if (strpos($class, NAMESPACE_PREFIX) === 0) {
                $class = substr($class, strlen(NAMESPACE_PREFIX));
            }
        }

        return explode('\\', $class);
    }

    /**
     * @param string $providedPath
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    private function getPath($providedPath)
    {
        if ($providedPath[0] == DIRECTORY_SEPARATOR) {
            $path = $providedPath;
        } else {
            $path = sprintf('%s%s%s', $this->getFixturePath(), DIRECTORY_SEPARATOR, $providedPath);
        }

        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf('No fixture found at %s', $path));
        }

        return $path;
    }
}
