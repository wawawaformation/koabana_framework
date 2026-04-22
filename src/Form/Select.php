<<<<<<< HEAD
<?php declare(strict_types=1);
=======
<?php

declare(strict_types=1);
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)

namespace Koabana\Form;

class Select extends Field
{
<<<<<<< HEAD
    /** @var array<string|int, string> */
    private array $options = [];

    /**
     * @param string $name
     * @param array<string|int, string> $options
     * @param array<string, mixed> $attributes
=======
    /** @var array<int|string, string> */
    private array $options = [];

    /**
     * @param string                    $name
     * @param array<int|string, string> $options
     * @param array<string, mixed>      $attributes
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
     */
    public function __construct(string $name, array $options = [], array $attributes = [])
    {
        parent::__construct($name, $attributes);
        $this->options = $options;
    }

<<<<<<< HEAD
    /** @return array<string|int, string> */
=======
    /** @return array<int|string, string> */
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
    public function getOptions(): array
    {
        return $this->options;
    }

    public function render(): string
    {
        $attributes = $this->getHtmlAttributes();
        $html = "<select name=\"{$this->name}\"{$attributes}>";

        foreach ($this->options as $value => $label) {
            $selected = $this->value === $value ? ' selected' : '';
<<<<<<< HEAD
            $value = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8');
=======
            $value = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
            $html .= "<option value=\"{$value}\"{$selected}>{$label}</option>";
        }

        $html .= '</select>';

        return $html;
    }
}
