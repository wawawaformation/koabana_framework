<?php

declare(strict_types=1);

namespace Tests\Support;

use Koabana\Controller\AbstractController;
use Koabana\Http\Session\SessionBag;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Contrôleur de test qui rend publiques les méthodes protégées d'AbstractController.
 */
final class ExposedController extends AbstractController
{
    /** @param array<string, string> $headers */
    public function callHtml(string $html, int $status = 200, array $headers = []): ResponseInterface
    {
        return $this->html($html, $status, $headers);
    }

    public function callText(string $text): ResponseInterface
    {
        return $this->text($text);
    }

    /** @param array<string, mixed> $data */
    public function callJson(array $data): ResponseInterface
    {
        return $this->json($data);
    }

    public function callNotFound(): never
    {
        $this->notFound('Article introuvable');
    }

    public function callRedirect(string $location, int $status): ResponseInterface
    {
        return $this->redirect($location, $status);
    }

    public function callIsLogged(ServerRequestInterface $request): bool
    {
        return $this->isLogged($request);
    }

    public function callSession(ServerRequestInterface $request): SessionBag
    {
        return $this->session($request);
    }

    public function callAddFlash(ServerRequestInterface $request): void
    {
        $this->addFlash($request, 'info', 'Message');
    }

    /** @return array<string, mixed> */
    public function callParsedBody(ServerRequestInterface $request): array
    {
        return $this->parsedBody($request);
    }
}
