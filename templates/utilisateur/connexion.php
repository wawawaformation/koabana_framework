<?php

/**
 * Template : Se connecter
 */

declare(strict_types=1);

use Koabana\Form\Form;
use Koabana\View\ViewContext;

/** @var ViewContext $view */
/** @var Form $form */
?>

<?php $view->start('title') ?>Se connecter<?php $view->end('title') ?>

<h1>Se connecter</h1>

<?= $form->open('/connexion', 'POST') ?>
<?= $form->csrf() ?>
<?php require __DIR__.'/../partials/form_fields.php'; ?>
<button type="submit">Se connecter</button>
<?= $form->close() ?>
<p><a href="/utilisateur/mot-de-passe-oublie">Mot de passe oublié ?</a> · <a href="/inscription">Créer un compte</a></p>
