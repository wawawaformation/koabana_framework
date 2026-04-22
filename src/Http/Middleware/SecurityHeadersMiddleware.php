<?php

declare(strict_types=1);

namespace Koabana\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Ajoute des en-têtes de sécurité HTTP à chaque réponse.
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
<<<<<<< HEAD
=======
        // Générer un nonce unique pour chaque requête
        $nonce = \bin2hex(\random_bytes(16));

        // Ajouter le nonce à la requête pour qu'il soit accessible dans les templates
        $request = $request->withAttribute('csp_nonce', $nonce);

>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        $response = $handler->handle($request);
        $isHttps = 'https' === $request->getUri()->getScheme();
        $appEnv = (string) (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod'));

        $cspParts = [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "form-action 'self'",
            "img-src 'self' data:",
<<<<<<< HEAD
            "script-src 'self'",
=======
            "script-src 'self' 'nonce-{$nonce}' 'wasm-unsafe-eval'",
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
            "style-src 'self' 'unsafe-inline'",
        ];

        if ($isHttps && 'prod' === $appEnv) {
            $cspParts[] = 'upgrade-insecure-requests';
        }

        $csp = implode('; ', $cspParts);

        $response = $response
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Content-Security-Policy', $csp)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()')
            ->withHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->withHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
        ;

        if ($isHttps && 'prod' === $appEnv) {
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

<<<<<<< HEAD
=======
        // Masquer les informations de version du serveur et de PHP
        $response = $response
            ->withoutHeader('X-Powered-By')
            ->withHeader('Server', 'Apache');

>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        return $response;
    }
}
