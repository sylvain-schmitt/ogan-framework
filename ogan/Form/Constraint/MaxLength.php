<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔒 MAX LENGTH CONSTRAINT
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Constraint;

class MaxLength implements ConstraintInterface
{
    private int $max;
    private string $message;

    public function __construct(int $max, ?string $message = null)
    {
        $this->max = $max;
        $this->message = $message ?? "This field must not exceed {$max} characters.";
    }

    public function validate(mixed $value, array $context = []): ?string
    {
        if ($value === null || $value === '') {
            return null; // Let Required handle empty values
        }

        if (strlen((string)$value) > $this->max) {
            return $this->message;
        }

        return null;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
