<?php

/**
 * Layout principal : squelette HTML commun à toutes les pages.
 */

declare(strict_types=1);

use Koabana\View\ViewContext;

/** @var ViewContext $view */
/** @var string $content */
/** @var array<string, list<string>> $flashes */
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $view->section('title', 'Accueil') ?> | Koabana</title>
    <link rel="stylesheet" href="/css/style.css">
    <?= $view->styleSheets() ?>
    <?= $view->headerJs() ?>
</head>

<body>
    <main id="main">
        <?php foreach ($flashes ?? [] as $type => $messages): ?>
            <div class="flash flash-<?= $view->e($type) ?>">
                <?php foreach ($messages as $message): ?>
                    <p><?= $view->e($message) ?></p>
                <?php endforeach ?>
            </div>
        <?php endforeach ?>

        <?= $content ?>
    </main>

    <?= $view->footerJs() ?>
</body>

</html>
