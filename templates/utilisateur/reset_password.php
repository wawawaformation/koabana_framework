<?php

/**
 * Template : Réinitialiser le mot de passe
 */

declare(strict_types=1);

use Koabana\Form\Form;
use Koabana\View\ViewContext;

/** @var ViewContext $view */
/** @var Form $form */
?>

<?php $view->start('title') ?>Réinitialiser le mot de passe<?php $view->end('title') ?>

<h1>Réinitialiser le mot de passe</h1>
<p>Choisissez votre nouveau mot de passe.</p>
<?= $form->open($view->e($urlForm), 'POST') ?>
<?= $form->csrf() ?>
<?php require __DIR__.'/../partials/form_fields.php'; ?>
<button type="submit">Enregistrer</button>
<?= $form->close() ?>
<p><a href="/connexion">Retour à la connexion</a></p>
