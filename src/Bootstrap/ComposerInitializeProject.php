<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Composer\Script\Event;

class ComposerInitializeProject
{
    public static function postCreateProject(Event $event)
    {
        $io = $event->getIO();
        $composer = $event->getComposer();
        $config = $composer->getConfig();
        $package = $composer->getPackage();

        $config->get('vendor-dir');
        // $config->merge();

        var_dump($config->all());
    }
}
