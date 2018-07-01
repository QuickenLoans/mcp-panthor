<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use QL\Panthor\Bootstrap\SlimEnvironmentFactory;
use Slim\App;
use Slim\CallableResolver;
use Slim\Collection;
use Slim\Handlers\Error;
use Slim\Handlers\PhpError;
use Slim\Handlers\NotAllowed;
use Slim\Handlers\NotFound;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ('env(SLIM_DISPLAY_ERRORS)',      false)
    ;

    $p
        ('slim.settings.http_version',                '1.1')
        ('slim.settings.chunk_size',                  4096)
        ('slim.settings.buffering',                   'append')
        ('slim.settings.determine_route_before_mw',   false)
        ('slim.settings.display_errors',              '%env(bool:SLIM_DISPLAY_ERRORS)%')

        ('slim.default_status_code', 200)

        ('slim.settings', [
            'httpVersion'                       => '%slim.settings.http_version%',
            'responseChunkSize'                 => '%slim.settings.chunk_size%',
            'outputBuffering'                   => '%slim.settings.buffering%',
            'determineRouteBeforeAppMiddleware' => '%slim.settings.determine_route_before_mw%',
            'displayErrorDetails'               => '%slim.settings.display_errors%',
        ])

        ('slim.default_request_headers', [
            'Content-Type' => 'text/html; charset=UTF-8'
        ])
    ;

    $s
        ->defaults()
            ->public()
    ;

    $s
        ('settings', Collection::class)
            ->arg('$items', '%slim.settings%')
        ('slim', App::class)
            ->arg('$container', ref('service_container'))

        ('environment', Environment::class)
            ->factory([SlimEnvironmentFactory::class, 'fromGlobal'])
        ('request', Request::class)
            ->factory([Request::class, 'createFromEnvironment'])
            ->arg('$environment', ref('environment'))
        ('response', Response::class)
            ->arg('$status', '%slim.default_status_code%')
            ->arg('$headers', ref('response.default_headers'))
        ('response.default_headers', Headers::class)
            ->arg('$items', '%slim.default_request_headers%')

        ('router', Router::class)
        ('callableResolver', CallableResolver::class)
            ->arg('$container', ref('service_container'))

        ('foundHandler', RequestResponse::class)
        ('notFoundHandler', NotFound::class)
        ('notAllowedHandler', NotAllowed::class)

        ('phpErrorHandler', PhpError::class)
            ->arg('$displayErrorDetails', '%slim.settings.display_errors%')
        ('errorHandler', Error::class)
            ->arg('$displayErrorDetails', '%slim.settings.display_errors%')
    ;
};
