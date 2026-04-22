# View System

Système de rendu de vues basé sur PHP natif, sans dépendance externe.

Fournit :
- **PhpTemplateRenderer** : Moteur de rendu avec layouts et output buffering
- **ViewContext** : Contexte partagé entre templates (sections, CSS, JS, profil)
- **Svg** : Utilitaire pour charger et modifier des SVG inline

## PhpTemplateRenderer

Moteur de rendu simple utilisant `require` et `ob_start()`.

### Initialisation

```php
<?php declare(strict_types=1);

namespace Koabana\Controller;

use Koabana\View\PhpTemplateRenderer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HomeController extends AbstractController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Injecte automatiquement le renderer via le DI
        // (voir config/containers.php)
        
        return $this->render($request, 'pages/home', [
            'title' => 'Accueil',
            'products' => $products,
        ]);
    }
}
```

Le renderer est injecté automatiquement dans AbstractController via le conteneur DI.

### Rendu simple (sans layout)

```php
return $this->render($request, 'pages/about', [], 200, null);
// ou
return $this->render($request, 'pages/about'); // layout par défaut
```

### Rendu avec layout

```php
// Utilise le layout par défaut (layout/main)
return $this->render($request, 'pages/contact', [
    'contact_form' => $form,
]);

// Layout personnalisé
return $this->render($request, 'pages/legal', [], 200, 'layout/clean');

// Pas de layout
return $this->render($request, 'pages/modal', [], 200, null);
```

### Structure des fichiers templates

```
templates/
├── layout/
│   ├── main.php          # Layout principal
│   ├── clean.php         # Layout minimaliste
│   └── admin.php         # Layout admin
├── pages/
│   ├── home.php
│   ├── contact.php
│   ├── about.php
│   └── legal.php
├── components/
│   ├── header.php
│   ├── footer.php
│   ├── nav.php
│   └── card.php
└── svgIcons/
    ├── arrow.svg
    ├── check.svg
    └── cross.svg
```

### Exemple de template (pages/product.php)

```php
<?php /** @var Koabana\View\ViewContext $view */ ?>
<?php /** @var array<int> $products */ ?>

<div class="products">
    <?php foreach ($products as $product): ?>
        <div class="card">
            <h3><?= htmlspecialchars($product['name']) ?></h3>
            <p><?= htmlspecialchars($product['description']) ?></p>
            <p class="price"><?= number_format($product['price'], 2) ?> €</p>
        </div>
    <?php endforeach; ?>
</div>
```

### Exemple de layout (layout/main.php)

```php
<?php /** @var Koabana\View\ViewContext $view */ ?>
<?php /** @var string $content */ ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Koabana') ?></title>
    
    <?= $view->styleSheets() ?>
    <?= $view->headerJs() ?>
</head>
<body>
    <?php require 'components/header.php'; ?>
    
    <main>
        <?= $content ?>
    </main>
    
    <?php require 'components/footer.php'; ?>
    
    <?= $view->footerJs() ?>
</body>
</html>
```

## ViewContext

Contexte de vue partagé entre tous les templates. Accessible via `$view`.

### Sections

Permet aux templates de définir des zones qui sont injectées dans le layout.

```php
<?php 
// Dans templates/pages/product.php

$view->start('sidebar');
?>
    <aside>
        <h2>Filtres</h2>
        <!-- Contenu du sidebar -->
    </aside>
<?php 
$view->end('sidebar');
?>

<!-- Contenu principal -->
<div class="main">
    <!-- ... -->
</div>
```

Dans le layout :
```php
<?php // layout/main.php ?>
<div class="container">
    <main><?= $content ?></main>
    <aside><?= $view->section('sidebar', '') ?></aside>
</div>

<?php 
// Modal de confirmation (si activé)
if ($view->isConfirmDeleteModalEnabled()): 
?>
    <?php require 'components/confirm-delete-modal.php'; ?>
<?php endif; ?>
```

### Feuilles de style

```php
<?php
// Dans un template

$view->addStyleSheet('/css/bootstrap.css');
$view->addStyleSheet('/css/custom.css', [
    'media' => 'screen and (min-width: 768px)',
]);
?>
```

Dans le layout :
```php
<?= $view->styleSheets() ?>
```

### Scripts JavaScript

```php
<?php
// Dans un template

// Header (avant le contenu)
$view->addHeaderJs('/js/bootstrap.js');

// Footer (fin de page, après le contenu)
$view->addFooterJs('/js/my-app.js', [
    'defer' => true,
]);
?>
```

Dans le layout :
```php
<head>
    <?= $view->headerJs() ?>
</head>
<body>
    <!-- Contenu -->
    <?= $view->footerJs() ?>
</body>
```

### Menu actif

```php
<?php
// Dans un contrôleur
$view->setActiveMenu('accueil'); // ou autre clé ALLOWED_MENUS
?>
```

Dans le layout :
```php
<nav>
    <ul>
        <li class="<?= $view->isActiveMenu('accueil') ? 'active' : '' ?>">
            <a href="/">Accueil</a>
        </li>
    </ul>
</nav>
```

### Modal de confirmation

```php
<?php 
// Dans un contrôleur

$view->enableConfirmDeleteModal();
$view->setActiveMenu('admin');
?>
```

### Profil utilisateur

```php
<?php
// Dans un contrôleur

$view->addProfileInfo([
    'first_name' => 'Alice',
    'last_name' => 'Dupont',
    'email' => 'alice@example.test',
]);
?>
```

Dans un template :
```php
<?php $profile = $view->getProfileInfos(); ?>
<p>Connecté : <?= htmlspecialchars($profile['first_name'] ?? '') ?></p>
```

## Svg

Utilitaire pour charger et modifier des SVG de manière typée.

### Initialisation

```php
<?php
use Koabana\View\Svg;

$svg = new Svg('/templates/svgIcons/arrow.svg');
?>
```

### Modifier les classes

```php
$svg->addClass('icon');
$svg->addClass('icon-lg');
$svg->removeClass('old-class');

echo $svg->render();
```

### Rôle et attributs ARIA

```php
$svg->setRole('img');
$svg->setAriaLabel('Flèche vers le haut');
$svg->setAriaLabelledBy('arrow-title');

echo $svg->render();
```

### Couleur (fill)

```php
$svg->setFill('currentColor');  // Hérite de la couleur CSS
$svg->setFill('#000');          // Noir
$svg->setFill('red');           // Couleur nommée
$svg->setFill(null);            // Réinitialise

echo $svg->render();
```

### Titre

```php
$svg->setTitle('Descendre');
$svg->setTitleId('custom-id');

// Rend : <title id="custom-id">Descendre</title>
// et ajoute aria-labelledby="custom-id"

echo $svg->render();
```

### Exemple complet (template)

```php
<?php 
use Koabana\View\Svg;

$checkIcon = new Svg('/templates/svgIcons/check.svg');
$checkIcon->addClass('icon');
$checkIcon->addClass('icon-success');
$checkIcon->setFill('currentColor');
$checkIcon->setRole('img');
$checkIcon->setAriaLabel('Succès');
?>

<div class="alert alert-success">
    <?= $checkIcon->render() ?>
    <span>Opération réussie !</span>
</div>
```

### Navigation entre les templates

```php
<?php
// Dans templates/pages/product.php

$view->setActiveMenu('accueil');

// Inclure un composant
require __DIR__ . '/../components/breadcrumb.php';
?>
```

### Exemple complet avec layout

**layout/main.php**
```php
<?php /** @var Koabana\View\ViewContext $view */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Koabana') ?></title>
    <?= $view->styleSheets() ?>
    <?= $view->headerJs() ?>
</head>
<body>
    <nav class="navbar">
        <a href="/" class="<?= $view->isActiveMenu('accueil') ? 'active' : '' ?>">Accueil</a>
    </nav>
    
    <main class="container">
        <?= $content ?>
    </main>
    
    <footer>
        <p>&copy; 2026 Koabana</p>
    </footer>
    
    <?= $view->footerJs() ?>
</body>
</html>
```

**pages/home.php**
```php
<?php /** @var Koabana\View\ViewContext $view */ ?>

<?php $view->setActiveMenu('accueil'); ?>

<h1>Bienvenue</h1>
<p><?= htmlspecialchars($greeting ?? '') ?></p>

<?php 
$view->addFooterJs('/js/home.js');
?>
```

Rendu final :
```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Koabana</title>
</head>
<body>
    <nav class="navbar">
        <a href="/" class="active">Accueil</a>
    </nav>
    
    <main class="container">
        <h1>Bienvenue</h1>
        <p>Bonjour !</p>
    </main>
    
    <footer>
        <p>&copy; 2026 Koabana</p>
    </footer>
    
    <script src="/js/home.js"></script>
</body>
</html>
```
