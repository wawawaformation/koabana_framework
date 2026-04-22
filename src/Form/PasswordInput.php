<<<<<<< HEAD
<?php declare(strict_types=1);
=======
<?php

declare(strict_types=1);
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)

namespace Koabana\Form;

class PasswordInput extends Field
{
    public function render(): string
    {
        $attributes = $this->getHtmlAttributes();

        return "<input type=\"password\" name=\"{$this->name}\"{$attributes}>";
    }
}
