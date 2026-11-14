<?php

/**
 * Template : tableau de bord (espace connecté)
 */

declare(strict_types=1);

use Koabana\View\ViewContext;

/** @var ViewContext $view */
/** @var array<string, mixed> $profile */
?>

<?php $view->start('title') ?>Tableau de bord<?php $view->end('title') ?>

<h1>Tableau de bord</h1>
<p>Bienvenue <?= $view->e((string) ($profile['user_firstname'] ?? '')) ?>.</p>

<h2>Mon compte</h2>
<ul>
    <li>Nom : <?= $view->e((string) ($profile['user_lastname'] ?? '')) ?></li>
    <li>Prénom : <?= $view->e((string) ($profile['user_firstname'] ?? '')) ?></li>
    <li>E-mail : <?= $view->e((string) ($profile['user_email'] ?? '')) ?></li>
</ul>
<p><a href="/deconnexion">Se déconnecter</a></p>
