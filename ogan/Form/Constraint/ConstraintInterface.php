<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔒 CONSTRAINT INTERFACE
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Interface for all form validation constraints.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Constraint;

interface ConstraintInterface
{
    /**
     * Validate a value
     * 
     * @param mixed $value The value to validate
     * @param array $context All form data (for cross-field validation)
     * @return string|null Error message or null if valid
     */
    public function validate(mixed $value, array $context = []): ?string;

    /**
     * Get the default error message
     * 
     * @return string
     */
    public function getMessage(): string;
}
