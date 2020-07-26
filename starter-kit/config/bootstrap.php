<?php

namespace ExampleApplication\Bootstrap;

use QL\Panthor\Bootstrap\DI;
use ExampleApplication\CachedContainer;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\DependencyInjection\ContainerBuilder;

$root = __DIR__ . '/..';
require_once "${root}/vendor/autoload.php";

if (class_exists(Dotenv::class)) {
    $dotenv = new Dotenv;
    $dotenv->loadEnv("${root}/config/.env", 'APP_ENV', 'dev', ['xxxxxx']);
}

$file = "${root}/src/CachedContainer.php";
$class = CachedContainer::class;
$options = [
    'class' => $class,
    'file' => $file
];

$di = DI::getDI($root, $options);

unset($root, $dotenv, $file, $class, $options); # Ensure cleanup so no variables leak from includes
return $di;
