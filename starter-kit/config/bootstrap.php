<?php
/**
 * @copyright (c) 2017 YourNameHere
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace ExampleApplication\Bootstrap;

use QL\Panthor\Bootstrap\DI;
use ExampleApplication\CachedContainer;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\DependencyInjection\ContainerBuilder;

$root = __DIR__ . '/..';
require_once "${root}/vendor/autoload.php";

if (class_exists(Dotenv::class)) {
    $dotenv = new Dotenv;

    if (file_exists("${root}/config/.env.default")) {
        $dotenv->load("${root}/config/.env.default");
    }

    if (file_exists("${root}/config/.env")) {
        $dotenv->load("${root}/config/.env");
    }
}

$file = "${root}/src/CachedContainer.php";
$class = CachedContainer::class;
$options = [
    'class' => $class,
    'file' => $file
];

return DI::getDI($root, $options);
