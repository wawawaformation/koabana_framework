<<<<<<< HEAD
<?php declare(strict_types=1);
=======
<?php

declare(strict_types=1);
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)

namespace Koabana\Form;

class TextInput extends Field
{
    public function render(): string
    {
<<<<<<< HEAD
        $value = htmlspecialchars((string)$this->value, ENT_QUOTES, 'UTF-8');
        $attributes = $this->getHtmlAttributes();

        return "<input type=\"text\" name=\"{$this->name}\" value=\"{$value}\"{$attributes}>";
=======
        $value = htmlspecialchars((string) $this->value, ENT_QUOTES, 'UTF-8');
        $attributes = $this->getHtmlAttributes();
        
        // Détermine le type en fonction des règles
        $type = 'text';
        if ($this->hasRule('email')) {
            $type = 'email';
        }

        return "<input type=\"{$type}\" name=\"{$this->name}\" value=\"{$value}\"{$attributes}>";
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
    }
}
