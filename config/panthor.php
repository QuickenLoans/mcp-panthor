<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use const E_ALL;
use const E_DEPRECATED;
use const E_USER_DEPRECATED;
use const JSON_UNESCAPED_SLASHES;
use Closure;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use QL\MCP\Common\Clock;
use QL\Panthor\Bootstrap\CacheableRouter;
use QL\Panthor\Bootstrap\GlobalMiddlewareLoader;
use QL\Panthor\Bootstrap\RouteLoader;
use QL\Panthor\Encryption\LibsodiumSymmetricCrypto;
use QL\Panthor\ErrorHandling\ContentHandler\HTMLTemplateContentHandler;
use QL\Panthor\ErrorHandling\ContentHandler\HTTPProblemContentHandler;
use QL\Panthor\ErrorHandling\ContentHandler\JSONContentHandler;
use QL\Panthor\ErrorHandling\ContentHandler\NegotiatingContentHandler;
use QL\Panthor\ErrorHandling\ContentHandler\PlainTextContentHandler;
use QL\Panthor\ErrorHandling\ErrorHandler;
use QL\Panthor\ErrorHandling\ExceptionHandler;
use QL\Panthor\HTTP\CookieEncryption\LibsodiumCookieEncryption;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\HTTPProblem\Renderer\JSONRenderer;
use QL\Panthor\Middleware\EncryptedCookiesMiddleware;
use QL\Panthor\Middleware\SessionMiddleware;
use QL\Panthor\Templating\TwigTemplate;
use QL\Panthor\Twig\Context;
use QL\Panthor\Twig\EnvironmentConfigurator;
use QL\Panthor\Twig\LazyTwig;
use QL\Panthor\Twig\TwigExtension;
use QL\Panthor\Utility\ClosureFactory;
use QL\Panthor\Utility\JSON;
use QL\Panthor\Utility\URI;
use Twig\Cache\FilesystemCache;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Template;

return function (ContainerConfigurator $container) {
    $s = $container->services();
    $p = $container->parameters();

    $p
        ('env(PANTHOR_APPROOT)',        __DIR__ . '/../../../..')

        ('env(PANTHOR_DEBUG)',          false)
        ('env(PANTHOR_SYMFONY_DEBUG)',  true)
        ('env(PANTHOR_TWIG_DEBUG)',     true)

        ('env(PANTHOR_TIMEZONE)',       'America/Detroit')
        ('env(PANTHOR_COOKIE_SECRET)',  '')
    ;

    $p
        ('routes',                      [])
        ('routes.cached',               '%env(PANTHOR_APPROOT)%/config/routes.cached.php')
        ('routes.cache_disabled',       true)

        ('global_middleware',           [])

        ('debug',                       '%env(bool:PANTHOR_DEBUG)%')
        ('symfony.debug',               '%env(bool:PANTHOR_SYMFONY_DEBUG)%')

        ('date.timezone',               '%env(PANTHOR_TIMEZONE)%')
        ('panthor.internal.timezone',   'UTC')

        ('twig.debug',                  '%env(bool:PANTHOR_TWIG_DEBUG)%')
        ('twig.template.dir',           '%env(PANTHOR_APPROOT)%/templates')
        ('twig.cache.dir',              '%env(PANTHOR_APPROOT)%/.twig')

        ('cookie.settings.lifetime',    '+1 year')
        ('cookie.settings.secure',      false)
        ('cookie.settings.http_only',   true)
        ('session.lifetime',            '+1 week')
        ('cookie.encryption.secret',    '%env(PANTHOR_COOKIE_SECRET)%')

        ('cookie.unencrypted',          [])
        ('cookie.delete_invalid',       true)
        ('cookie.settings', [
            'maxAge'    => '%cookie.settings.lifetime%',
            'path'      => '/',
            'domain'    => '',
            'secure'    => '%cookie.settings.secure%',
            'httpOnly'  => '%cookie.settings.http_only%',
        ])

        ('error_handling.levels',           E_ALL)
        ('error_handling.thrown_errors',    E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED)
        ('error_handling.logged_errors',    E_ALL)
        ('error_handling.log_stacktrace',   false)
        ('error_handling.html_template',    'error.html.twig')

        ('middleware.session_options', [
            'lifetime' => '%session.lifetime%'
        ])
    ;

    $s
        ->defaults()
            ->public()
    ;

    // Core services. Available for use by applications
    $s
        (CacheableRouter::class)
            ->call('setCaching', ['%routes.cached%', '%routes.cache_disabled%'])
        (RouteLoader::class)
            ->arg('$routes', '%routes%')

        (URI::class)
            ->arg('$router', ref(CacheableRouter::class))
        (JSON::class)
        (Clock::class)
            ->arg('$current', 'now')
            ->arg('$timezone', '%panthor.internal.timezone%')
        (LoggerInterface::class, NullLogger::class)

        (ErrorHandler::class)
            ->arg('$handler', ref(ExceptionHandler::class))
            ->arg('$logger', ref(LoggerInterface::class))
            ->call('setStacktraceLogging', ['%error_handling.log_stacktrace%'])
            ->call('setThrownErrors', ['%error_handling.thrown_errors%'])
            ->call('setLoggedErrors', ['%error_handling.logged_errors%'])

        (ExceptionHandler::class)
            ->arg('$handler', ref('content_handler'))
            ->arg('$request', ref('request'))
            ->arg('$response', ref('response'))
        ('problem.renderer', JSONRenderer::class)
            ->arg('$json', ref('panthor.problem.json'))

        ('content_handler', NegotiatingContentHandler::class)
            ->arg('$handlers', [
                '*/*'                   => ref('panthor.content_handler.html'),
                'text/html'             => ref('panthor.content_handler.html'),
                'application/problem'   => ref('panthor.content_handler.problem'),
                'application/json'      => ref('panthor.content_handler.json'),
                'text/plain'            => ref('panthor.content_handler.text'),
            ])

        ('cookie.encryption', LibsodiumCookieEncryption::class)
            ->arg('$crypto', ref('panthor.libsodium.encryption'))
        (CookieHandler::class)
            ->arg('$cookieSettings', '%cookie.settings%')

        ('twig.template', LazyTwig::class)
            ->arg('$environment', ref(Environment::class))
            ->arg('$context', ref(Context::class))
        (Context::class)
        ('twig.loader', FilesystemLoader::class)
            ->arg('$paths', '%twig.template.dir%')
            ->arg('$rootPath', '%env(PANTHOR_APPROOT)%')
        (Environment::class)
            ->arg('$loader', ref('twig.loader'))
            ->configurator([ref('panthor.twig.configurator'), 'configure'])
            ->call('addExtension', [ref('panthor.twig.extension')])

        (GlobalMiddlewareLoader::class)
            ->arg('$di', ref('service_container'))
            ->arg('$middleware', '%global_middleware%')
    ;

    // Optional services. Depending on the type of application you may or may not need these.
    $s
        (EncryptedCookiesMiddleware::class)
            ->arg('$encryption', ref('cookie.encryption'))
            ->arg('$unencryptedCookies', '%cookie.unencrypted%')
            ->arg('$deleteInvalid', '%cookie.delete_invalid%')

        (SessionMiddleware::class)
            ->arg('$handler', ref(CookieHandler::class))
            ->arg('$options', '%middleware.session_options%')
    ;

    // Overrides. Change built-in Slim handlers to Panthor handlers
    $s
        ('notFoundHandler', Closure::class)
            ->factory([ClosureFactory::class, 'buildClosure'])
            ->args(['$service', ref('content_handler'), 'handleNotFound'])
        ('notAllowedHandler', Closure::class)
            ->factory([ClosureFactory::class, 'buildClosure'])
            ->args(['$service', ref('content_handler'), 'handleNotAllowed'])
        ('phpErrorHandler', Closure::class)
            ->factory([ClosureFactory::class, 'buildClosure'])
            ->args(['$service', ref('content_handler'), 'handleThrowable'])
        ('phpErrorHandler', Closure::class)
            ->factory([ClosureFactory::class, 'buildClosure'])
            ->args(['$service', ref('content_handler'), 'handleException'])
    ;

    // Support classes. Users shouldn't need to interact with these
    $s
        ('panthor.libsodium.encryption', LibsodiumSymmetricCrypto::class)
            ->arg('$secret', '%cookie.encryption.secret%')

        ('panthor.problem.json', JSON::class)
            ->call('addEncodingOptions', [JSON_UNESCAPED_SLASHES])

        ('panthor.twig.extension', TwigExtension::class)
            ->arg('$uri', ref(URI::class))
            ->arg('$clock', ref(Clock::class))
            ->arg('$timezone', '%date.timezone%')
            ->arg('$isDebugMode', '%debug%')
        ('panthor.twig.configurator', EnvironmentConfigurator::class)
            ->arg('$debugMode', '%twig.debug%')
            ->arg('$cache', ref('panthor.twig.cache'))
        ('panthor.twig.cache', FilesystemCache::class)
            ->arg('$directory', '%twig.cache.dir%')

        ('panthor.content_handler.html', HTMLTemplateContentHandler::class)
            ->arg('$template', ref('panthor.handler.twig'))
            ->arg('$displayErrorDetails', '%slim.settings.display_errors%')
            ->call('setStacktraceLogging', ['%error_handling.log_stacktrace%'])
        ('panthor.content_handler.problem', HTTPProblemContentHandler::class)
            ->arg('$renderer', ref('problem.renderer'))
            ->arg('$displayErrorDetails', '%slim.settings.display_errors%')
            ->call('setStacktraceLogging', ['%error_handling.log_stacktrace%'])
        ('panthor.content_handler.json', JSONContentHandler::class)
            ->arg('$json', ref(JSON::class))
            ->arg('$displayErrorDetails', '%slim.settings.display_errors%')
            ->call('setStacktraceLogging', ['%error_handling.log_stacktrace%'])
        ('panthor.content_handler.text', PlainTextContentHandler::class)
            ->arg('$displayErrorDetails', '%slim.settings.display_errors%')
            ->call('setStacktraceLogging', ['%error_handling.log_stacktrace%'])

        ('panthor.handler.twig', TwigTemplate::class)
            ->arg('$twig', ref('panthor.handler.twig_environment'))
            ->arg('$context', ref(Context::class))
        ('panthor.handler.twig_environment', Template::class)
            ->factory([ref(Environment::class), 'loadTemplate'])
            ->arg('$name', '%error_handling.html_template%')
    ;
};
