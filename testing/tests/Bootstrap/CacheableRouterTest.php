<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use PHPUnit_Framework_TestCase;

class CacheableRouterTest extends PHPUnit_Framework_TestCase
{
    public $cacheFile;

    public function setUp()
    {
        $this->cacheFile = __DIR__ . '/generated.cached.routes.php';
    }

    public function tearDown()
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function testNoFileGeneratedIfCacheDisabled()
    {
        $router = new CacheableRouter;
        $router->setCaching($this->cacheFile, true);

        $router->initializeDispatcher();

        $this->assertFileNotExists($this->cacheFile);
    }

    public function testFileGeneratedIfCacheEnabled()
    {
        $router = new CacheableRouter;
        $router->setCaching($this->cacheFile, false);

        $router->initializeDispatcher();

        $this->assertFileExists($this->cacheFile);
    }

    public function testGeneratedCacheFileIsCorrect()
    {
        $router = new CacheableRouter;
        $router->setCaching($this->cacheFile, false);
        $router->map(['GET'], '/page', function() {});

        $router->initializeDispatcher();

        $expected = <<<'ROUTE_FILE'
<?php return array (
  0 => 
  array (
    'GET' => 
    array (
      '/page' => 'route0',
    ),
  ),
  1 => 
  array (
  ),
);
ROUTE_FILE;

        $this->assertFileExists($this->cacheFile);
        $this->assertEquals($expected, file_get_contents($this->cacheFile));
    }
}
