<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ☑️ CHECKBOXTYPE - Type de Champ Case à Cocher
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Génère une case à cocher (<input type="checkbox">).
 * 
 * EXEMPLE D'UTILISATION :
 * ------------------------
 * 
 * ->add('accept_terms', CheckboxType::class, [
 *     'label' => 'J\'accepte les conditions d\'utilisation',
 *     'required' => true
 * ])
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Types;

class CheckboxType implements FieldTypeInterface
{
    public function render(string $name, mixed $value, array $options, array $errors): string
    {
        $label = $options['label'] ?? ucfirst($name);
        $required = $options['required'] ?? false;
        $attr = $options['attr'] ?? [];
        $valueAttr = $options['value'] ?? '1'; // Valeur envoyée si coché
        $checked = $options['checked'] ?? false; // État par défaut

        // Si une valeur est fournie (depuis handleRequest), l'utiliser
        if ($value !== '' && $value !== null) {
            $checked = true;
        }

        $html = '<div class="form-group">';
        
        $html .= '<input type="checkbox"';
        $html .= ' id="' . htmlspecialchars($name) . '"';
        $html .= ' name="' . htmlspecialchars($name) . '"';
        $html .= ' value="' . htmlspecialchars((string)$valueAttr) . '"';
        if ($required) {
            $html .= ' required';
        }
        if ($checked) {
            $html .= ' checked';
        }

        // Attributs HTML
        foreach ($attr as $key => $val) {
            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
        }

        $html .= '>';

        // Label après la checkbox (convention HTML)
        $html .= '<label for="' . htmlspecialchars($name) . '">';
        if ($required) {
            $html .= '<span class="required">*</span> ';
        }
        $html .= htmlspecialchars($label);
        $html .= '</label>';

        // Afficher les erreurs
        if (!empty($errors)) {
            $html .= '<div class="errors">';
            foreach ($errors as $error) {
                $html .= '<span class="error">' . htmlspecialchars($error) . '</span>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    public function renderWidget(string $name, mixed $value, array $options): string
    {
        $required = $options['required'] ?? false;
        $attr = $options['attr'] ?? [];

        $html = '<input type="checkbox"';
        $html .= ' id="' . htmlspecialchars($name) . '"';
        $html .= ' name="' . htmlspecialchars($name) . '"';
        $html .= ' value="1"';
        if ($value) {
            $html .= ' checked';
        }
        if ($required) {
            $html .= ' required';
        }
        foreach ($attr as $key => $val) {
            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
        }
        $html .= '>';
        return $html;
    }
}

