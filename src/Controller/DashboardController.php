<?php

declare(strict_types=1);

namespace Koabana\Controller;

use Koabana\View\PhpTemplateRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Contrôleur de la page d'accueil.
 */
final class DashboardController extends AbstractController
{
    public function __construct(PhpTemplateRenderer $view)
    {
        parent::__construct($view);
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        if (null !== $redirect = $this->requireLogin($request)) {
            return $redirect;
        }

        $profile = $this->getUserProfile($request);

        return $this->render(
            $request,
            'dashboard/index',
        );
    }
}
