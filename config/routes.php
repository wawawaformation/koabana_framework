<?php

declare(strict_types=1);

return [
    /*
    ['GET', '/demo', \Koabana\Controller\DemoController::class],
    ['GET', '/demo/form', [\Koabana\Controller\DemoController::class, 'form']],
    ['POST', '/demo/submit', [\Koabana\Controller\DemoController::class, 'submit']],
    ['GET', '/demo/form-demo', [\Koabana\Controller\DemoController::class, 'formDemo']],
    ['POST', '/demo/form-demo', [\Koabana\Controller\DemoController::class, 'formDemo']],
    ['GET', '/demo/tests', [\Koabana\Controller\DemoController::class, 'testBags']],
    ['GET', '/demo/session/set', [\Koabana\Controller\DemoController::class, 'sessionSet']],
    ['GET', '/demo/session/view', [\Koabana\Controller\DemoController::class, 'sessionView']],
    ['GET', '/demo/profile/login', [\Koabana\Controller\DemoController::class, 'profileLogin']],
    ['GET', '/demo/profile/logout', [\Koabana\Controller\DemoController::class, 'profileLogout']],
    ['GET', '/demo/flash/add', [\Koabana\Controller\DemoController::class, 'flashAdd']],
*/


    ['GET', '/', [\Koabana\Controller\HomeController::class, 'index']],


    ['GET', '/connexion', [\Koabana\Controller\LoginController::class, 'login']],
    ['POST', '/connexion', [\Koabana\Controller\LoginController::class, 'login']],
    ['GET', '/deconnexion', [\Koabana\Controller\LoginController::class, 'logout']],
    ['GET', '/utilisateur/mot-de-passe-oublie', [\Koabana\Controller\LoginController::class, 'forgotPassword']],
    ['POST', '/utilisateur/mot-de-passe-oublie', [\Koabana\Controller\LoginController::class, 'forgotPassword']],
    ['GET', '/reinitialisation-mot-de-passe/{token: [a-f0-9]{32}}', [\Koabana\Controller\LoginController::class, 'resetPassword']],
    ['POST', '/reinitialisation-mot-de-passe/{token: [a-f0-9]{32}}', [\Koabana\Controller\LoginController::class, 'resetPassword']],


    ['GET', '/inscription', [\Koabana\Controller\RegisterController::class, 'register']],
    ['POST', '/inscription', [\Koabana\Controller\RegisterController::class, 'register']],
    ['GET', '/inscription/success', [\Koabana\Controller\RegisterController::class, 'success']],
    ['GET', '/activate/{token: [a-f0-9]{16}}', [\Koabana\Controller\RegisterController::class, 'activate']],
    ['POST', '/activate/{token: [a-f0-9]{16}}', [\Koabana\Controller\RegisterController::class, 'activate']],

    ['GET', '/mon-espace', [\Koabana\Controller\DashboardController::class, 'index']],


];