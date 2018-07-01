<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

class ComposerInitializeProject
{
    private static $starterPath = '/starter-kit';

    public static function postCreateProject(Event $event)
    {
        $io = $event->getIO();

        $file = Factory::getComposerFile();
        $projectRoot = realpath(dirname($file));

        if (!self::validateProject($io, $projectRoot)) {
            return;
        }

        $fs = new Filesystem;
        $targetDir = $projectRoot;
        $sourceDir = $projectRoot . self::$starterPath;
        $tempDir = '/..' . self::$starterPath . '-' . mt_rand(10000, 20000);
        $tempStarterDir = $projectRoot . $tempDir;

        static::write($io, 'Mirroring starter-kit files');
        $fs->mirror($sourceDir, $tempStarterDir);
        $fs->mirror($tempStarterDir, $targetDir, null, ['delete' => true]);
        $fs->remove($tempStarterDir);

        static::write($io, 'Writing config file to ./config/.env.default');
        self::writeConfig($targetDir);

        $fs->mkdir($targetDir . '/.twig');

        static::write($io, "");
        static::write($io, "!!! Installation not yet finished !!!");
        static::write($io, "Change to your project directory and run 'composer install'");
        static::write($io, "");
    }

    /**
     * @param IOInterface $io
     * @param string $projectRoot
     *
     * @return bool
     */
    private static function validateProject(IOInterface $io, string $projectRoot)
    {
        $jsonFile = $projectRoot . '/composer.json';
        $lockFile = $projectRoot . '/composer.lock';

        if (file_exists($lockFile)) {
            $io->writeError('Detected composer.lock. Aborting initialization of MCP Panthor.');
            return false;
        }

        $composerFile = json_decode(file_get_contents($jsonFile), true);

        $name = $composerFile['name'] ?? '';
        if ($name !== 'ql/mcp-panthor') {
            $io->writeError('MCP Panthor not detected. Something may have gone wrong during \'composer create-project\'.');
            return false;
        }

        return true;
    }

    /**
     * @param string $projectRoot
     *
     * @return void
     */
    private static function writeConfig(string $projectRoot)
    {
        $envFile = $projectRoot . '/config/.env.default';
        $contents = file_get_contents($envFile);

        $cookieSecret = bin2hex(random_bytes(64));
        $contents = str_replace('{PLACEHOLDER_COOKIE_SECRET}', $cookieSecret, $contents);

        file_put_contents($envFile, $contents);
    }

    /**
     * @param IOInterface $io
     * @param string $message
     *
     * @return void
     */
    private static function write(IOInterface $io, string $message)
    {
        $msg = [
            '[Panthor]: ' . $message,
        ];

        $io->write($msg);
    }
}
