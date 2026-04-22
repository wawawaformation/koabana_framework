<?php

declare(strict_types=1);

namespace Koabana\Controller;

use Koabana\View\PhpTemplateRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Contrôleur de la page d'accueil.
 */
final class HomeController extends AbstractController
{
    public function __construct(PhpTemplateRenderer $view)
    {
        parent::__construct($view);
    }

    /**
     * @param array<string, string> $args
     *
     * @return ResponseInterface
     */
    public function index(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $html = '<h1>Welcome to Koabana Framework</h1><p>This is the home page.</p>';

        return $this->render(
            $request,
            'home/index',
            [
                'name' => 'Page de test',
            ],
        );
    }
}
