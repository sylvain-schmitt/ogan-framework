<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔒 CALLBACK CONSTRAINT
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Custom validation via callback function
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Constraint;

class Callback implements ConstraintInterface
{
    /** @var callable */
    private $callback;
    private string $message;

    /**
     * @param callable $callback Function that returns true if valid, false or string if invalid
     * @param string $message Default error message if callback returns false
     */
    public function __construct(callable $callback, string $message = 'This value is not valid.')
    {
        $this->callback = $callback;
        $this->message = $message;
    }

    public function validate(mixed $value, array $context = []): ?string
    {
        $result = call_user_func($this->callback, $value, $context);

        if ($result === true) {
            return null;
        }

        if (is_string($result)) {
            return $result;
        }

        return $this->message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
