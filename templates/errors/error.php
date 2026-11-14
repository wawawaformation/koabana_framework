<?php

/**
 * Template : page d'erreur HTTP (4xx).
 * Une application peut la remplacer pour un code précis avec errors/{code}.php (ex. errors/404.php).
 */

declare(strict_types=1);

use Koabana\View\ViewContext;

/** @var ViewContext $view */
/** @var int $status */
/** @var string $title */
/** @var string $message */
?>

<?php $view->start('title') ?><?= $view->e($title) ?><?php $view->end('title') ?>

<h1><?= $view->e($title) ?></h1>
<p><?= $view->e($message) ?></p>
<p>Code d'erreur : <?= $status ?></p>
<p><a href="/">Retour à l'accueil</a></p>
