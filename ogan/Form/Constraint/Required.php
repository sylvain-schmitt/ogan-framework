<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔒 REQUIRED CONSTRAINT
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Constraint;

class Required implements ConstraintInterface
{
    private string $message;

    public function __construct(string $message = 'This field is required.')
    {
        $this->message = $message;
    }

    public function validate(mixed $value, array $context = []): ?string
    {
        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
            return $this->message;
        }

        return null;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
