<<<<<<< HEAD
<?php declare(strict_types=1);
=======
<?php

declare(strict_types=1);
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)

namespace Koabana\Form;

class Validator
{
    /** @var array<string, array<string>> */
    private array $errors = [];

    /**
     * Valide un ensemble de champs selon leurs règles
<<<<<<< HEAD
=======
     *
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
     * @param array<Field> $fields
     */
    public function validate(array $fields): bool
    {
        $this->errors = [];

        /** @var Field $field */
        foreach ($fields as $field) {
            $this->validateField($field);
        }

        return empty($this->errors);
    }

<<<<<<< HEAD
=======
    /** @return array<string, array<string>> */
    public function getErrors(): array
    {
        return [];
        // Les erreurs sont directement stockées dans les Field
    }

>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
    /**
     * Valide un champ individuel
     */
    private function validateField(Field $field): void
    {
        $rules = $field->getRules();
        $value = $field->getValue();

        // Vérification required
        if (isset($rules['required'])) {
<<<<<<< HEAD
            if ($rules['required'] && (empty($value) && $value !== '0' && $value !== 0)) {
                $field->addError("Ce champ est requis.");
=======
            if ($rules['required'] && (empty($value) && '0' !== $value && 0 !== $value)) {
                $field->addError('Ce champ est requis.');

>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
                return;
            }
        }

        // Si vide et pas required, on skip les autres validations
<<<<<<< HEAD
        if (empty($value) && $value !== '0' && $value !== 0) {
=======
        if (empty($value) && '0' !== $value && 0 !== $value) {
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
            return;
        }

        // Validations supplémentaires
        if (isset($rules['email'])) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $field->addError("Format d'email invalide.");
            }
        }

        if (isset($rules['minLength'])) {
<<<<<<< HEAD
            $minLength = (int)$rules['minLength'];
            if (strlen((string)$value) < $minLength) {
=======
            $minLength = (int) $rules['minLength'];
            if (strlen((string) $value) < $minLength) {
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
                $field->addError("Minimum {$minLength} caractères requis.");
            }
        }

        if (isset($rules['maxLength'])) {
<<<<<<< HEAD
            $maxLength = (int)$rules['maxLength'];
            if (strlen((string)$value) > $maxLength) {
=======
            $maxLength = (int) $rules['maxLength'];
            if (strlen((string) $value) > $maxLength) {
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
                $field->addError("Maximum {$maxLength} caractères.");
            }
        }

        if (isset($rules['min'])) {
<<<<<<< HEAD
            $min = (int)$rules['min'];
            if ((int)$value < $min) {
=======
            $min = (int) $rules['min'];
            if ((int) $value < $min) {
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
                $field->addError("La valeur doit être au minimum {$min}.");
            }
        }

        if (isset($rules['max'])) {
<<<<<<< HEAD
            $max = (int)$rules['max'];
            if ((int)$value > $max) {
=======
            $max = (int) $rules['max'];
            if ((int) $value > $max) {
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
                $field->addError("La valeur ne doit pas dépasser {$max}.");
            }
        }

        if (isset($rules['regex'])) {
<<<<<<< HEAD
            if (!preg_match($rules['regex'], (string)$value)) {
=======
            if (!preg_match($rules['regex'], (string) $value)) {
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
                $field->addError("Le format n'est pas valide.");
            }
        }
    }
<<<<<<< HEAD

    /** @return array<string, array<string>> */
    public function getErrors(): array
    {
        /** @var array<string, array<string>> $errors */
        $errors = [];
        // Les erreurs sont directement stockées dans les Field
        return $errors;
    }
=======
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
}
