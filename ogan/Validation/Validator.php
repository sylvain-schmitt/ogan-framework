<?php

namespace Ogan\Validation;

class Validator
{
    private array $errors = [];

    /**
     * Valide des données par rapport à des règles.
     * 
     * @param array $data Données à valider ($_POST)
     * @param array $rules Règles (ex: ['email' => 'required|email'])
     * @return array Tableau des erreurs (vide si succès)
     */
    public function validate(array $data, array $rules): array
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $rulesList = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($rulesList as $rule) {
                // Parse rule definition (e.g., "min:3")
                if (str_contains($rule, ':')) {
                    [$ruleName, $param] = explode(':', $rule, 2);
                } else {
                    $ruleName = $rule;
                    $param = null;
                }

                $methodName = 'validate' . ucfirst($ruleName);
                if (method_exists($this, $methodName)) {
                    if (!$this->$methodName($value, $param, $field)) {
                        break; // Stop at first error for this field
                    }
                }
            }
        }

        return $this->errors;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    // ─────────────────────────────────────────────────────────────
    // Règles de validation
    // ─────────────────────────────────────────────────────────────

    private function validateRequired($value, $param, $field): bool
    {
        if ($value === null || trim((string)$value) === '') {
            $this->addError($field, "Le champ {$field} est requis.");
            return false;
        }
        return true;
    }

    private function validateEmail($value, $param, $field): bool
    {
        if (empty($value)) return true; // Required handles empty check
        
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "Le champ {$field} doit être un email valide.");
            return false;
        }
        return true;
    }

    private function validateMin($value, $param, $field): bool
    {
        if (empty($value)) return true;
        
        if (strlen((string)$value) < (int)$param) {
            $this->addError($field, "Le champ {$field} doit contenir au moins {$param} caractères.");
            return false;
        }
        return true;
    }

    private function validateMax($value, $param, $field): bool
    {
        if (empty($value)) return true;
        
        if (strlen((string)$value) > (int)$param) {
            $this->addError($field, "Le champ {$field} ne doit pas dépasser {$param} caractères.");
            return false;
        }
        return true;
    }

    private function validateNumeric($value, $param, $field): bool
    {
        if (empty($value)) return true;
        
        if (!is_numeric($value)) {
            $this->addError($field, "Le champ {$field} doit être numérique.");
            return false;
        }
        return true;
    }
}
