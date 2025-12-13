<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔒 EQUAL TO CONSTRAINT
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Validates that a field equals another field (e.g., password confirmation)
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Constraint;

class EqualTo implements ConstraintInterface
{
    private string $field;
    private string $message;

    /**
     * @param string $field The field name to compare with
     * @param string|null $message Custom error message
     */
    public function __construct(string $field, ?string $message = null)
    {
        $this->field = $field;
        $this->message = $message ?? "This field must match the {$field} field.";
    }

    public function validate(mixed $value, array $context = []): ?string
    {
        if ($value === null || $value === '') {
            return null; // Let Required handle empty values
        }

        $compareValue = $context[$this->field] ?? null;

        if ($value !== $compareValue) {
            return $this->message;
        }

        return null;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
