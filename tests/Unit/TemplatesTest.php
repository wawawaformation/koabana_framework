<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Form\Form;
use Koabana\Form\TextInput;
use Koabana\View\PhpTemplateRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie que chaque template appelé par les contrôleurs existe et se rend dans le layout.
 *
 * @internal
 *
 * @coversNothing
 */
final class TemplatesTest extends TestCase
{
    #[DataProvider('templates')]
    public function testTemplateRendersInsideLayout(string $template, string $title): void
    {
        $renderer = new PhpTemplateRenderer(\dirname(__DIR__, 2).'/templates');

        $html = $renderer->render($template, $this->data());

        self::assertStringContainsString('<!DOCTYPE html>', $html);
        self::assertStringContainsString("<h1>{$title}</h1>", $html);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function templates(): iterable
    {
        yield 'connexion' => ['utilisateur/connexion', 'Se connecter'];

        yield 'mot de passe oublié' => ['utilisateur/mot_de_passe_oublie', 'Mot de passe oublié'];

        yield 'réinitialisation' => ['utilisateur/reset_password', 'Réinitialiser le mot de passe'];

        yield 'inscription' => ['register/register', 'Inscription'];

        yield 'inscription réussie' => ['register/success', 'Inscription réussie'];

        yield 'création du mot de passe' => ['register/set_password', 'Créer votre mot de passe'];

        yield 'tableau de bord' => ['dashboard/index', 'Tableau de bord'];
    }

    public function testDashboardLogoutIsAPostFormWithCsrfToken(): void
    {
        $html = new PhpTemplateRenderer(\dirname(__DIR__, 2).'/templates')->render('dashboard/index', $this->data());

        self::assertStringContainsString('<form method="post" action="/deconnexion">', $html);
        self::assertStringContainsString('<input type="hidden" name="_csrf_token" value="jeton">', $html);
        self::assertStringNotContainsString('href="/deconnexion"', $html);
    }

    // Données transmises par les contrôleurs (et par AbstractController::render)
    /**
     * @return array<string, mixed>
     */
    private function data(): array
    {
        $form = new Form('test');
        $form->add(new TextInput('email', ['label' => 'Adresse email']));
        $form->setCsrfToken('jeton');

        return [
            'form' => $form,
            'token' => 'abcdef0123456789',
            'urlForm' => '/reinitialisation-mot-de-passe/abc',
            'userId' => 1,
            'flashes' => [],
            'profile' => ['user_firstname' => 'Ada', 'user_lastname' => 'Lovelace', 'user_email' => 'ada@example.com'],
            'csrf_token' => 'jeton',
        ];
    }
}
