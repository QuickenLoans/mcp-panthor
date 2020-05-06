<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use QL\Panthor\Bootstrap\CacheableRouteCollectorConfigurator;
use QL\Panthor\Bootstrap\CacheableRouter;
use QL\Panthor\Bootstrap\SlimEnvironmentFactory;
use Slim\App;
use Slim\CallableResolver;
use Slim\Factory\AppFactory;
use Slim\Factory\Psr17\SlimPsr17Factory;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\ErrorMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteResolver;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ('env(SLIM_DISPLAY_ERRORS)',    '0')
        ('env(SLIM_LOG_ERRORS)',        '0')
        ('env(SLIM_LOG_ERROR_DETAILS)', '0')

        ('env(SLIM_ROUTING_CACHE_FILE)',        __DIR__ . '/../../../../config/routes.cached.php')
        ('env(SLIM_ROUTING_IS_CACHE_DISABLED)', '1')
    ;

    $p
        ('slim.body_parsers', [])

        ('slim.settings.display_errors',    '%env(bool:SLIM_DISPLAY_ERRORS)%')
        ('slim.settings.log_errors',        '%env(bool:SLIM_LOG_ERRORS)%')
        ('slim.settings.log_error_details', '%env(bool:SLIM_LOG_ERROR_DETAILS)%')

        ('slim.routing.cache_file',        '%env(string:SLIM_ROUTING_CACHE_FILE)%')
        ('slim.routing.is_cache_disabled', '%env(bool:SLIM_ROUTING_IS_CACHE_DISABLED)%')

        // ('slim.default_request_headers', [
        //     'Content-Type' => 'text/html; charset=UTF-8'
        // ])
    ;

    $s
        ->defaults()
            ->public()
    ;

    $s
        ('slim', App::class)
            ->factory([AppFactory::class, 'createFromContainer'])
            ->arg('$container', ref('service_container'))

        (ResponseFactoryInterface::class)
            ->factory([SlimPsr17Factory::class, 'getResponseFactory'])

        (RouteCollectorInterface::class, RouteCollector::class)
            ->arg('$responseFactory', ref(ResponseFactoryInterface::class))
            ->arg('$callableResolver', ref(CallableResolverInterface::class))
            ->arg('$container', ref('service_container'))
            ->arg('$defaultInvocationStrategy', ref('slim.invocation_strategy'))
            ->configurator(ref(CacheableRouteCollectorConfigurator::class))

        (RouteResolverInterface::class, RouteResolver::class)
            ->arg('$routeCollector', ref(RouteCollectorInterface::class))
        (CallableResolverInterface::class, CallableResolver::class)
            ->arg('$container', ref('service_container'))
    ;

    $s
        (BodyParsingMiddleware::class)
            ->arg('$bodyParsers', '%slim.body_parsers%')

        (ErrorMiddleware::class)
            ->arg('$callableResolver', ref(CallableResolverInterface::class))
            ->arg('$responseFactory', ref(ResponseFactoryInterface::class))
            ->arg('$displayErrorDetails', '%slim.settings.display_errors%')
            ->arg('$logErrors', '%slim.settings.log_errors%')
            ->arg('$logErrorDetails', '%slim.settings.log_error_details%')
            ->arg('$logger', ref(LoggerInterface::class))

        (RoutingMiddleware::class)
            ->arg('$routeResolver', ref(RouteResolverInterface::class))
            ->arg('$routeParser', ref(RouteParserInterface::class))
    ;

    $s
        (CacheableRouteCollectorConfigurator::class)
            ->arg('$cacheFile', '%slim.routing.cache_file%')
            ->arg('$isCacheDisabled', '%slim.routing.is_cache_disabled%')

        (RouteParserInterface::class)
            ->factory([ref(RouteCollectorInterface::class), 'getRouteParser'])

        ('slim.invocation_strategy', RequestResponse::class)

        (LoggerInterface::class, NullLogger::class)

        // ('request', Request::class)
        //     ->factory([ServerRequestFactory::class, 'createFromGlobals'])
        // ('response', Response::class)
        //     ->arg('$status', '%slim.default_status_code%')
        //     ->arg('$headers', ref('response.default_headers'))
        // ('response.default_headers', Headers::class)
        //     ->arg('$items', '%slim.default_request_headers%')

        // ('router', CacheableRouter::class)

        // ('notFoundHandler', NotFound::class)
        // ('notAllowedHandler', NotAllowed::class)

        // ('phpErrorHandler', PhpError::class)
        //     ->arg('$displayErrorDetails', '%slim.settings.display_errors%')
        // ('errorHandler', Error::class)
        //     ->arg('$displayErrorDetails', '%slim.settings.display_errors%')
    ;
};
