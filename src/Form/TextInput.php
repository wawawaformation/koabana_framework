<?php

declare(strict_types=1);

namespace Koabana\Form;

class TextInput extends Field
{
    public function render(): string
    {
        $value = htmlspecialchars((string) $this->value, ENT_QUOTES, 'UTF-8');
        $attributes = $this->getHtmlAttributes();
        
        // Détermine le type en fonction des règles
        $type = 'text';
        if ($this->hasRule('email')) {
            $type = 'email';
        }

        return "<input type=\"{$type}\" name=\"{$this->name}\" value=\"{$value}\"{$attributes}>";
    }
}
