<?php

namespace ExampleApplication\Bootstrap;

use QL\Panthor\Bootstrap\GlobalMiddlewareLoader;
use QL\Panthor\Bootstrap\RouteLoader;
use QL\Panthor\ErrorHandling\ErrorHandler;
use QL\Panthor\ErrorHandling\ExceptionHandler;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Middleware\ErrorMiddleware;

$root = realpath(__DIR__ . '/..');

$container = require "${root}/config/bootstrap.php";

ini_set('session.use_cookies', '0');
ini_set('display_errors', 0);

// Initialize the HTTP request
$request = $container->get(ServerRequestCreatorInterface::class)
    ->createServerRequestFromGlobals();

// Error handling
$exceptionHandler = $container->get(ExceptionHandler::class)
    ->attachRequest($request);

$container->get(ErrorMiddleware::class)
    ->setDefaultErrorHandler($exceptionHandler);

$container->get(ErrorHandler::class)
    ->register()
    ->registerShutdown();

// Build Slim application
$app = $container->get('slim');

// Load routes onto Slim
$container->get(RouteLoader::class)($app);

// Add global middleware to Slim
$container->get(GlobalMiddlewareLoader::class)($app);

$app->run($request);
