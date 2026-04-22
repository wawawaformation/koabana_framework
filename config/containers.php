<?php

declare(strict_types=1);


use function DI\env;
use function DI\get;
use function DI\autowire;
use Psr\Container\ContainerInterface;
use DI\Container;
use Psr\Log\LoggerInterface;
use \Koabana\Database\BDDFactory;
use Koabana\Log\LoggerFactory;
use Koabana\View\PhpTemplateRenderer;
use Monolog\Level;
use function DI\factory;
use Koabana\Mail\MailerFactory;
use PHPMailer\PHPMailer\PHPMailer;

return [
    /*
     |------------------------------------------------------------
     | Environnement
     |------------------------------------------------------------
     | Priorité :
     | - variables d'environnement (env)
     | - fallback
     */
    'app.env' => env('APP_ENV', 'dev'),
    'app.debug' => env('APP_DEBUG', '1') === '1',

    /*
     |------------------------------------------------------------
     | Chemins utiles
     |------------------------------------------------------------
     |
     */
    'paths.root' => dirname(__DIR__),
    'paths.app' => __DIR__,
    'paths.public' => __DIR__ . '/public',
    'paths.var' => __DIR__ . '/var',
    'paths.templates' => dirname(__DIR__) . '/templates',

    /*
     |------------------------------------------------------------
     | Exemples de bindings plus tard
     |------------------------------------------------------------
     | Interface => Implémentation
     | Psr\Log\LoggerInterface::class => ...
     | Koabana\Contracts\FooInterface::class => Koabana\Service\Foo::class,
     */

   BDDFactory::class => autowire(),


    LoggerFactory::class => autowire()
        ->constructor(
            name: 'koabana',
            filePath: dirname(__DIR__) . '/var/log/app.log',
            level: Level::Debug,
        ),

    LoggerInterface::class => factory(function (LoggerFactory $factory) {
        return $factory->create();
    }),

    PhpTemplateRenderer::class => autowire()
        ->constructor(
            templatesPath: get('paths.templates'),
        ),

    MailerFactory::class => autowire(),

    PHPMailer::class => factory(function (MailerFactory $factory) {
        return $factory->create();
    }),

   


    
];
