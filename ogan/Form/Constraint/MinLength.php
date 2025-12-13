<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔒 MIN LENGTH CONSTRAINT
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Constraint;

class MinLength implements ConstraintInterface
{
    private int $min;
    private string $message;

    public function __construct(int $min, ?string $message = null)
    {
        $this->min = $min;
        $this->message = $message ?? "This field must be at least {$min} characters.";
    }

    public function validate(mixed $value, array $context = []): ?string
    {
        if ($value === null || $value === '') {
            return null; // Let Required handle empty values
        }

        if (strlen((string)$value) < $this->min) {
            return $this->message;
        }

        return null;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
