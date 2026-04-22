# AbstractController

Classe abstraite de base pour tous les contrôleurs de l'application.

Fournit des méthodes utilitaires pour produire des réponses HTTP (templates, JSON, redirections, erreurs) et accéder aux sessions/profil utilisateur.

## Héritage

Tout contrôleur doit etendre `AbstractController` et implémenter ses actions :

```php
<?php declare(strict_types=1);

namespace Koabana\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class PageController extends AbstractController
{
    public function home(ServerRequestInterface $request): ResponseInterface
    {
        return $this->render($request, 'pages/home', [
            'title' => 'Accueil',
        ]);
    }
}
```

## Méthodes courantes

### `render()` – Rendre un template

```php
return $this->render(
    $request,
    'pages/contact',           // template
    ['form' => $form],         // données
    200,                       // code HTTP (optionnel)
    'layout/main',             // layout (optionnel)
);
```

Le template reçoit automatiquement :
- `$flashes` : messages flash consommés
- `$profile` : profil utilisateur (ProfileBag)
- `$csrf_token` : token CSRF pour formulaires
- `$csp_nonce` : nonce CSP

### `json()` – Reponse JSON

```php
return $this->json([
    'status' => 'success',
    'data' => $data,
], 200);
```

### `redirect()` – Redirection

```php
return $this->redirect('/dashboard', 302);
```

### `html()`, `text()` – Contenu brut

```php
return $this->html('<h1>Contenu HTML</h1>');
return $this->text('Contenu texte');
```

### `notFound()` – Erreur 404

```php
return $this->notFound('Page non trouvee');
```

## Sessions et Profil

### `flash()` – Acceder aux FlashBag

```php
$flash = $this->flash($request);
$flash->add('success', 'Votre profil a ete mis a jour.');
```

Ou via `addFlash()` :

```php
$this->addFlash($request, 'error', 'Une erreur s\'est produite.');
```

### `session()` – Acceder aux SessionBag

```php
$session = $this->session($request);
$session->set('cart', ['item_1' => 2]);
$cart = $session->get('cart', []);
```

### `profile()` – Acceder au ProfileBag (utilisateur)

```php
$profile = $this->profile($request);

if ($profile->isLogged()) {
    echo "Connecte : " . $profile->getFirstname();
} else {
    echo "Utilisateur non connecte";
}
```

## Exemple complet

```php
<?php declare(strict_types=1);

namespace Koabana\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class UserController extends AbstractController
{
    public function profile(ServerRequestInterface $request): ResponseInterface
    {
        $profile = $this->profile($request);

        if (!$profile->isLogged()) {
            return $this->redirect('/login', 302);
        }

        return $this->render($request, 'user/profile', [
            'user_id' => $profile->getId(),
            'firstname' => $profile->getFirstname(),
        ]);
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $profile = $this->profile($request);

        if (!$profile->isLogged()) {
            return $this->redirect('/login', 302);
        }

        // Logique metier
        $updated = true;

        if ($updated) {
            $this->addFlash($request, 'success', 'Profil mis a jour !');
            return $this->redirect('/profile', 302);
        }

        $this->addFlash($request, 'error', 'Erreur lors de la mise a jour.');
        return $this->redirect('/profile', 302);
    }
}
```
