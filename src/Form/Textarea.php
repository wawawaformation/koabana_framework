<<<<<<< HEAD
<?php declare(strict_types=1);
=======
<?php

declare(strict_types=1);
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)

namespace Koabana\Form;

class Textarea extends Field
{
    public function render(): string
    {
<<<<<<< HEAD
        $value = htmlspecialchars((string)$this->value, ENT_QUOTES, 'UTF-8');
=======
        $value = htmlspecialchars((string) $this->value, ENT_QUOTES, 'UTF-8');
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        $attributes = $this->getHtmlAttributes();

        return "<textarea name=\"{$this->name}\"{$attributes}>{$value}</textarea>";
    }
}
