<?php

namespace ExampleApplication\Bootstrap;

use QL\Panthor\Bootstrap\GlobalMiddlewareLoader;
use QL\Panthor\Bootstrap\RouteLoader;
use QL\Panthor\ErrorHandling\ErrorHandler;
use QL\Panthor\ErrorHandling\ExceptionHandler;

$root = realpath(__DIR__ . '/..');

$container = require "${root}/config/bootstrap.php";

// Error handling
$handler = $container->get(ErrorHandler::class)
    ->register()
    ->registerShutdown();

ini_set('session.use_cookies', '0');
ini_set('display_errors', 0);

// Build Slim application
$app = $container->get('slim');

// Load routes onto Slim
$container->get(RouteLoader::class)($app);

// Add global middleware to Slim
$container->get(GlobalMiddlewareLoader::class)($app);

// Attach Slim to exception handler for error rendering
$container->get(ExceptionHandler::class)->attachSlim($app);

$app->run();
