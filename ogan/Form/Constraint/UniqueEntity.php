<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔒 UNIQUE ENTITY CONSTRAINT
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Validates that a value is unique in the database
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Constraint;

class UniqueEntity implements ConstraintInterface
{
    private string $modelClass;
    private string $field;
    private string $message;
    private ?int $excludeId;

    /**
     * @param string $modelClass The model class (e.g., User::class)
     * @param string $field The field to check uniqueness on
     * @param string|null $message Custom error message
     * @param int|null $excludeId ID to exclude (for updates)
     */
    public function __construct(
        string $modelClass, 
        string $field, 
        ?string $message = null,
        ?int $excludeId = null
    ) {
        $this->modelClass = $modelClass;
        $this->field = $field;
        $this->message = $message ?? "This {$field} is already used.";
        $this->excludeId = $excludeId;
    }

    public function validate(mixed $value, array $context = []): ?string
    {
        if ($value === null || $value === '') {
            return null; // Let Required handle empty values
        }

        // Build finder method name (e.g., findByEmail for email field)
        $finderMethod = 'findBy' . ucfirst($this->field);
        
        if (!method_exists($this->modelClass, $finderMethod)) {
            // Fallback: try to use a generic where query
            return $this->validateWithWhere($value);
        }

        $existing = call_user_func([$this->modelClass, $finderMethod], $value);

        if ($existing) {
            // If we're updating, exclude the current record
            if ($this->excludeId !== null && $existing->getId() === $this->excludeId) {
                return null;
            }
            return $this->message;
        }

        return null;
    }

    /**
     * Fallback validation using where() method
     */
    private function validateWithWhere(mixed $value): ?string
    {
        if (!method_exists($this->modelClass, 'where')) {
            // Can't validate, skip
            return null;
        }

        $existing = call_user_func([$this->modelClass, 'where'], $this->field, '=', $value);
        
        if (!empty($existing)) {
            $first = is_array($existing) ? reset($existing) : $existing;
            if ($this->excludeId !== null && method_exists($first, 'getId') && $first->getId() === $this->excludeId) {
                return null;
            }
            return $this->message;
        }

        return null;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
