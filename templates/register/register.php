<?php

/**
 * Template : Inscription
 */

declare(strict_types=1);

use Koabana\Form\Form;
use Koabana\View\ViewContext;

/** @var ViewContext $view */
/** @var Form $form */
?>

<?php $view->start('title') ?>Inscription<?php $view->end('title') ?>

<h1>Inscription</h1>
<p>Créez votre compte : un e-mail d'activation vous sera envoyé.</p>
<?= $form->open('/inscription', 'POST') ?>
<?= $form->csrf() ?>
<?php require __DIR__.'/../partials/form_fields.php'; ?>
<button type="submit">Créer mon compte</button>
<?= $form->close() ?>
<p>Déjà inscrit ? <a href="/connexion">Se connecter</a></p>
