<?php

declare(strict_types=1);

namespace Koabana\Form;

class Validator
{
    /**
     * Valide un ensemble de champs selon leurs règles.
     * Les erreurs sont stockées dans chaque Field.
     *
     * @param array<Field> $fields
     *
     * @return bool true si aucun champ n'a d'erreur
     */
    public function validate(array $fields): bool
    {
        $isValid = true;

        foreach ($fields as $field) {
            // Repart de zéro : une validation précédente ne doit pas laisser d'erreurs
            $field->clearErrors();
            $this->validateField($field);

            if ($field->hasErrors()) {
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * Valide un champ individuel
     */
    private function validateField(Field $field): void
    {
        $rules = $field->getRules();
        $value = $field->getValue();

        // Vérification required
        if (isset($rules['required'])) {
            if ($rules['required'] && (empty($value) && '0' !== $value && 0 !== $value)) {
                $field->addError('Ce champ est requis.');

                return;
            }
        }

        // Si vide et pas required, on skip les autres validations
        if (empty($value) && '0' !== $value && 0 !== $value) {
            return;
        }

        // Validations supplémentaires
        if (isset($rules['email'])) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $field->addError("Format d'email invalide.");
            }
        }

        if (isset($rules['minLength'])) {
            $minLength = (int) $rules['minLength'];
            if (mb_strlen((string) $value) < $minLength) {
                $field->addError("Minimum {$minLength} caractères requis.");
            }
        }

        if (isset($rules['maxLength'])) {
            $maxLength = (int) $rules['maxLength'];
            if (mb_strlen((string) $value) > $maxLength) {
                $field->addError("Maximum {$maxLength} caractères.");
            }
        }

        if (isset($rules['min'])) {
            $min = (int) $rules['min'];
            if ((int) $value < $min) {
                $field->addError("La valeur doit être au minimum {$min}.");
            }
        }

        if (isset($rules['max'])) {
            $max = (int) $rules['max'];
            if ((int) $value > $max) {
                $field->addError("La valeur ne doit pas dépasser {$max}.");
            }
        }

        if (isset($rules['regex'])) {
            if (!preg_match($rules['regex'], (string) $value)) {
                $field->addError("Le format n'est pas valide.");
            }
        }
    }
}
