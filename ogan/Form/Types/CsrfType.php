<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 CSRF TYPE
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Hidden field type for CSRF token protection.
 * Automatically generates and validates tokens.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Types;

use Ogan\Security\CsrfTokenManager;

class CsrfType implements FieldTypeInterface
{
    private CsrfTokenManager $csrfManager;

    public function __construct()
    {
        $this->csrfManager = new CsrfTokenManager();
    }

    /**
     * Render the CSRF hidden field
     */
    public function render(string $name, mixed $value, array $options, array $errors): string
    {
        $tokenId = $options['token_id'] ?? 'form';
        $token = $this->csrfManager->getToken($tokenId);

        $html = '<input type="hidden"';
        $html .= ' id="' . htmlspecialchars($name) . '"';
        $html .= ' name="' . htmlspecialchars($name) . '"';
        $html .= ' value="' . htmlspecialchars($token) . '"';
        $html .= '>';

        // Display errors if any
        if (!empty($errors)) {
            $html .= '<div class="text-red-500 text-sm mt-1">';
            foreach ($errors as $error) {
                $html .= '<span>' . htmlspecialchars($error) . '</span>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Render just the widget (same as render for hidden fields)
     */
    public function renderWidget(string $name, mixed $value, array $options): string
    {
        $tokenId = $options['token_id'] ?? 'form';
        $token = $this->csrfManager->getToken($tokenId);

        return '<input type="hidden" id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Validate the CSRF token
     */
    public function validate(string $name, mixed $value, array $options, array $allData): array
    {
        $tokenId = $options['token_id'] ?? 'form';
        
        if (!$this->csrfManager->isTokenValid($tokenId, $value)) {
            return ['Token CSRF invalide. Veuillez réessayer.'];
        }

        return [];
    }

    /**
     * Get the token manager (for external validation if needed)
     */
    public function getCsrfManager(): CsrfTokenManager
    {
        return $this->csrfManager;
    }
}
