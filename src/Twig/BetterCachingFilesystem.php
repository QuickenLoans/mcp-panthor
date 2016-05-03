<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Twig;

use Twig_Loader_Filesystem;

/**
 * Extends and replaces the original twig environment to allow server-independent caching.
 *
 * Twig_Loader_Filesystem uses the full absolute path for determining the cache key which can change depending on the server
 * the code is pushed to.
 *
 * Normally, cache keys are determined by hashing the entire absolute path to a template.
 *
 * This will break if you build your cached templates on a different server than your deploy to (Such as the standard
 * method of having a build server and a web server). Instead, when determining a cache key, this checks all potential
 * template paths, and strips them from the absolute path to that file that the standard Twig_Filesystem uses.
 *
 * Cache keys are then hashed from the relative path to the template file.
 *
 * *********
 * CAUTION !
 * *********
 *
 * This has not been tested when using multiple twig template directories and the use of this class is not
 * recommended in that scenario.
 *
 */
class BetterCachingFilesystem extends Twig_Loader_Filesystem
{
    /**
     * @inheritDoc
     */
    public function getCacheKey($name)
    {
        $fullPath = $this->findTemplate($name);

        foreach ($this->getPaths() as $path) {

            // resolve relative path
            $path = realpath($path);

            if (strpos($fullPath, $path) === 0) {
                $fullPath = substr($fullPath, strlen($path) + 1);
                break;
            }
        }

        return $fullPath;
    }
}
