<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ExampleApplication\HelloWorldController;
use ExampleApplication\TestCookieController;
use ExampleApplication\TestSessionController;
use QL\Panthor\Twig\LazyTwig;

return function (ContainerConfigurator $container) {
    $s = $container->services();

    $s->defaults()
        ->autowire();

    $s
        ('hello.page', HelloWorldController::class)
            ->arg('$template', twig('hello.twig'))
        ('session.page', TestSessionController::class)
            ->arg('$template', twig('hello.twig'))
        ('cookie.page', TestCookieController::class)
            ->arg('$template', twig('hello.twig'))
    ;
};

function twig($template) {
    return inline(LazyTwig::class)
        ->arg('$template', $template)
        ->autowire();
}
