<?php

/**
 * Template : Mot de passe oublié
 */

declare(strict_types=1);

use Koabana\Form\Form;
use Koabana\View\ViewContext;

/** @var ViewContext $view */
/** @var Form $form */
?>

<?php $view->start('title') ?>Mot de passe oublié<?php $view->end('title') ?>

<h1>Mot de passe oublié</h1>
<p>Saisissez votre adresse e-mail pour recevoir un lien de réinitialisation.</p>
<?= $form->open('/utilisateur/mot-de-passe-oublie', 'POST') ?>
<?= $form->csrf() ?>
<?php require __DIR__.'/../partials/form_fields.php'; ?>
<button type="submit">Envoyer le lien</button>
<?= $form->close() ?>
<p><a href="/connexion">Retour à la connexion</a></p>
