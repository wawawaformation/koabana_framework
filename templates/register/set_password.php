<?php

/**
 * Template : Créer votre mot de passe
 */

declare(strict_types=1);

use Koabana\Form\Form;
use Koabana\View\ViewContext;

/** @var ViewContext $view */
/** @var Form $form */
?>

<?php $view->start('title') ?>Créer votre mot de passe<?php $view->end('title') ?>

<h1>Créer votre mot de passe</h1>
<p>Votre compte est activé. Dernière étape : choisissez un mot de passe d'au moins 8 caractères.</p>
<?= $form->open('/activate/'.$view->e($token), 'POST') ?>
<?= $form->csrf() ?>
<?php require __DIR__.'/../partials/form_fields.php'; ?>
<button type="submit">Créer mon mot de passe</button>
<?= $form->close() ?>

