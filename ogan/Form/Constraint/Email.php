<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔒 EMAIL CONSTRAINT
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Constraint;

class Email implements ConstraintInterface
{
    private string $message;

    public function __construct(string $message = 'Please enter a valid email address.')
    {
        $this->message = $message;
    }

    public function validate(mixed $value, array $context = []): ?string
    {
        if ($value === null || $value === '') {
            return null; // Let Required handle empty values
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->message;
        }

        return null;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
