<?php

/**
 * Partiel : champs d'un formulaire avec libellé, aide et erreurs.
 * Usage dans un template : require __DIR__.'/../partials/form_fields.php';
 */

declare(strict_types=1);

use Koabana\Form\Form;
use Koabana\View\ViewContext;

/** @var ViewContext $view */
/** @var Form $form */
$errors = $form->errors();
?>
<?php foreach ($form->getFields() as $fieldName => $field): ?>
    <div class="field<?= empty($errors[$fieldName]) ? '' : ' error' ?>">
        <label for="<?= $view->e($fieldName) ?>"><?= $view->e((string) $field->getAttribute('label')) ?></label>
        <?php if (null !== $field->getAttribute('infos')): ?>
            <span class="field-help"><?= $view->e((string) $field->getAttribute('infos')) ?></span>
        <?php endif ?>
        <?= $field->render() ?>
        <?php foreach ($errors[$fieldName] ?? [] as $error): ?>
            <p class="field-error"><?= $view->e($error) ?></p>
        <?php endforeach ?>
    </div>
<?php endforeach ?>
