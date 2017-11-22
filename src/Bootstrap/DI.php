<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use RuntimeException;
use Symfony\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Utility to handle container building and caching.
 */
class DI
{
    const PRIMARY_CONFIGURATION_FILE = 'config/config.yaml';
    const ENV_CACHE_DISABLED = 'PANTHOR_DI_DISABLE_CACHE_ON';

    // When the container is built (dev-mode), this dumps and loads the cached
    // container instead of using "ContainerBuilder"
    const BUILD_AND_CACHE = false;

    const DI_EXTENSIONS = [];

    /**
     * @param string $root
     * @param bool $resolveEnvironment
     *
     * @return ContainerBuilder
     */
    public static function buildDI($root, $resolveEnvironment = false)
    {
        $root = rtrim($root, '/');

        $container = new ContainerBuilder;
        $loader = new YamlFileLoader($container, new FileLocator($root));

        $extensions = [];
        foreach (static::DI_EXTENSIONS as $extClass) {
            if (!class_exists($extClass)) {
                throw new RuntimeException("Symfony DI Extension not found: \"${extClass}\"");
            }

            $extensions[] = new $extClass;
        }

        foreach ($extensions as $ext) {
            $container->registerExtension($ext);
        }

        $loader->load(static::PRIMARY_CONFIGURATION_FILE);

        foreach ($extensions as $ext) {
            $container->loadFromExtension($ext->getAlias());
        }

        $container->compile($resolveEnvironment);

        return $container;
    }

    /**
     * @param string $root
     * @param array $options
     *
     * @return ContainerBuilder|null
     */
    public static function getDI($root, array $options)
    {
        $class = $options['class'] ?: '';
        if (!$class) {
            return null;
        }

        $cacheDisabled = getenv(static::ENV_CACHE_DISABLED);

        if ($cacheDisabled) {

            $container = static::buildDI($root, !static::BUILD_AND_CACHE);

            if (static::BUILD_AND_CACHE) {
                $cached = static::cacheDI($container, $options);

                $content = str_replace('<?php', '', $cached);
                eval($content);
                $container = new $class;
            }

        } else {
            if (!class_exists($class)) {
                throw new RuntimeException("DI Cached Container class not found: \"${class}\"");
            }

            $container = new $class;
        }

        return $container;
    }

    /**
     * @param ContainerBuilder $container
     * @param array $options
     *
     * @return string|null The cached container file contents.
     */
    public static function cacheDI(ContainerBuilder $container, array $options)
    {
        $class = $options['class'] ?: '';
        if (!$class) {
            return null;
        }

        $exploded = explode('\\', $class);
        $config = array_merge($options, [
            'class' => array_pop($exploded),
            'namespace' => implode('\\', $exploded)
        ]);

        $dumper = new PhpDumper($container);
        if (class_exists(ProxyDumper::class)) {
            $dumper->setProxyDumper(new ProxyDumper);
        }

        return $dumper->dump($config);
    }
}
