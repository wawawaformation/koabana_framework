<?php

declare(strict_types=1);

namespace Koabana\Form;

abstract class Field
{
    protected string $name;
    protected mixed $value = null;

    /** @var array<string, mixed> */
    protected array $attributes = [];

    /** @var array<string, mixed> */
    protected array $rules = [];

    /** @var array<string> */
    protected array $errors = [];

    /**
     * @param string               $name
     * @param array<string, mixed> $attributes
     */
    public function __construct(string $name, array $attributes = [])
    {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->extractRules();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    /** @return array<string, mixed> */
    public function getRules(): array
    {
        return $this->rules;
    }

    public function hasRule(string $rule): bool
    {
        return isset($this->rules[$rule]);
    }

    public function getRule(string $rule): mixed
    {
        return $this->rules[$rule] ?? null;
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    /** @return array<string> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Méthode abstraite pour le rendu HTML
     */
    abstract public function render(): string;

    /** @return array<string, mixed> */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $key): bool|int|string|null
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Retourne les attributs HTML (inclut les règles de validation converties en attributs HTML)
     */
    protected function getHtmlAttributes(): string
    {
        $html = $this->getNonValidationAttributes();
        $html .= $this->getValidationAttributes();

        return $html;
    }

    /**
     * Retourne les attributs non-validation
     */
    private function getNonValidationAttributes(): string
    {
        /** @var array<string> $validationKeys */
        $validationKeys = ['required', 'email', 'minLength', 'maxLength', 'regex', 'min', 'max', 'match', 'label', 'infos'];
        $html = '';

        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $validationKeys, true)) {
                if (true === $value) {
                    $html .= " {$key}";
                } elseif (false !== $value) {
                    $html .= " {$key}=\"".htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'"';
                }
            }
        }

        return $html;
    }

    /**
     * Retourne les attributs HTML de validation
     */
    protected function getValidationAttributes(): string
    {
        $html = '';

        // Ajoute les attributs HTML de validation
        if ($this->hasRule('required')) {
            $html .= ' required';
        }

        if ($this->hasRule('minLength')) {
            $minLength = (int) $this->getRule('minLength');
            $html .= " minlength=\"{$minLength}\"";
        }

        if ($this->hasRule('maxLength')) {
            $maxLength = (int) $this->getRule('maxLength');
            $html .= " maxlength=\"{$maxLength}\"";
        }

        if ($this->hasRule('min')) {
            $min = (int) $this->getRule('min');
            $html .= " min=\"{$min}\"";
        }

        if ($this->hasRule('max')) {
            $max = (int) $this->getRule('max');
            $html .= " max=\"{$max}\"";
        }

        if ($this->hasRule('regex')) {
            $pattern = (string) $this->getRule('regex');
            // Nettoie le pattern en enlevant les délimiteurs regex
            $pattern = preg_replace('#^/|/$#', '', $pattern);
            $html .= " pattern=\"".htmlspecialchars($pattern, ENT_QUOTES, 'UTF-8').'"';
        }

        return $html;
    }

    /**
     * Extrait les règles de validation des attributs
     */
    private function extractRules(): void
    {
        /** @var array<string> $validationKeys */
        $validationKeys = ['required', 'email', 'minLength', 'maxLength', 'regex', 'min', 'max', 'match'];

        foreach ($validationKeys as $key) {
            if (isset($this->attributes[$key])) {
                $this->rules[$key] = $this->attributes[$key];
            }
        }
    }
}
