<<<<<<< HEAD
<?php declare(strict_types=1);
=======
<?php

declare(strict_types=1);
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)

namespace Koabana\Form;

class Checkbox extends Field
{
    public function render(): string
    {
<<<<<<< HEAD
        $checked = (bool)$this->value ? ' checked' : '';
=======
        $checked = (bool) $this->value ? ' checked' : '';
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        $attributes = $this->getHtmlAttributes();

        return "<input type=\"checkbox\" name=\"{$this->name}\" value=\"1\"{$checked}{$attributes}>";
    }
}
