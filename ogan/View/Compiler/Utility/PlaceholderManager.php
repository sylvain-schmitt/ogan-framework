<?php

namespace Ogan\View\Compiler\Utility;

/**
 * Gère les placeholders pour protéger les chaînes et variables pendant la compilation
 */
class PlaceholderManager
{
    private array $placeholders = [];
    private int $placeholderIndex = 0;

    /**
     * Protège une chaîne en la remplaçant par un placeholder
     * 
     * @param string $content Contenu dans lequel protéger la chaîne
     * @param string $string Chaîne à protéger
     * @param string $prefix Préfixe du placeholder (ex: 'STRING', 'VAR')
     * @return string Contenu avec placeholder
     */
    public function protect(string $content, string $string, string $prefix = 'PROTECTED'): string
    {
        $placeholder = '##' . $prefix . '_' . $this->placeholderIndex . '##';
        $this->placeholders[$placeholder] = $string;
        $this->placeholderIndex++;

        return str_replace($string, $placeholder, $content);
    }

    /**
     * Restaure tous les placeholders dans le contenu
     * 
     * @param string $content Contenu avec placeholders
     * @return string Contenu avec placeholders restaurés
     */
    public function restore(string $content): string
    {
        foreach ($this->placeholders as $placeholder => $original) {
            $content = str_replace($placeholder, $original, $content);
        }

        return $content;
    }

    /**
     * Réinitialise les placeholders
     */
    public function reset(): void
    {
        $this->placeholders = [];
        $this->placeholderIndex = 0;
    }

    /**
     * Retourne tous les placeholders
     * 
     * @return array Placeholders
     */
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }
}
