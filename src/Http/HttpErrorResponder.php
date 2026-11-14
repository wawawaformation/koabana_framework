<?php

declare(strict_types=1);

namespace Koabana\Http;

use GuzzleHttp\Psr7\Response;
use Koabana\View\PhpTemplateRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Transforme un code d'erreur HTTP (4xx) en réponse : page HTML dans le layout, ou JSON.
 *
 * - JSON si l'en-tête Accept contient « json », HTML sinon ;
 * - template errors/{code}.php s'il existe, sinon errors/error.php ;
 * - textes en français tirés de MESSAGES, jamais du message technique de l'exception.
 */
final class HttpErrorResponder
{
    /** @var array<int, array{string, string}> titre et message par code */
    private const array MESSAGES = [
        400 => ['Requête invalide', "La requête envoyée n'a pas pu être traitée."],
        403 => ['Accès refusé', "Vous n'avez pas accès à cette page, ou votre session a expiré. Rechargez la page puis réessayez."],
        404 => ['Page introuvable', "La page demandée n'existe pas ou a été déplacée."],
        405 => ['Méthode non autorisée', "Cette action n'est pas possible sur cette page."],
    ];

    public function __construct(
        private readonly PhpTemplateRenderer $renderer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, string|string[]> $headers en-têtes à conserver (ex. Allow sur une 405)
     */
    public function respond(ServerRequestInterface $request, int $status, array $headers = []): ResponseInterface
    {
        [$title, $message] = self::MESSAGES[$status]
            ?? ['Erreur '.$status, 'Une erreur est survenue lors du traitement de la requête.'];

        if (\str_contains($request->getHeaderLine('Accept'), 'json')) {
            $json = \json_encode(['status' => $status, 'error' => $title], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            return new Response($status, ['Content-Type' => 'application/json; charset=utf-8'] + $headers, $json);
        }

        $template = $this->renderer->exists('errors/'.$status) ? 'errors/'.$status : 'errors/error';

        try {
            $html = $this->renderer->render($template, ['status' => $status, 'title' => $title, 'message' => $message]);
        } catch (\Throwable $exception) {
            // Template d'erreur défaillant : on ne laisse pas l'exception sortir du gestionnaire d'erreurs
            $this->logger->error('Error page rendering failed', ['template' => $template, 'exception' => $exception]);

            return new Response($status, ['Content-Type' => 'text/plain; charset=utf-8'] + $headers, $title);
        }

        return new Response($status, ['Content-Type' => 'text/html; charset=utf-8'] + $headers, $html);
    }
}
