<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔘 RADIOTYPE - Type de Champ Bouton Radio
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Génère des boutons radio (<input type="radio">).
 * 
 * EXEMPLE D'UTILISATION :
 * ------------------------
 * 
 * ->add('gender', RadioType::class, [
 *     'label' => 'Genre',
 *     'choices' => [
 *         'male' => 'Homme',
 *         'female' => 'Femme',
 *         'other' => 'Autre'
 *     ],
 *     'required' => true
 * ])
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Types;

class RadioType implements FieldTypeInterface
{
    public function render(string $name, mixed $value, array $options, array $errors): string
    {
        $label = $options['label'] ?? ucfirst($name);
        $required = $options['required'] ?? false;
        $attr = $options['attr'] ?? [];
        $choices = $options['choices'] ?? [];
        $inline = $options['inline'] ?? false; // Afficher en ligne ou en colonne

        $html = '<div class="form-group">';
        $html .= '<label>' . htmlspecialchars($label);
        if ($required) {
            $html .= ' <span class="required">*</span>';
        }
        $html .= '</label>';

        $containerClass = $inline ? 'radio-group-inline' : 'radio-group';
        $html .= '<div class="' . $containerClass . '">';

        foreach ($choices as $choiceValue => $choiceLabel) {
            $radioId = $name . '_' . $choiceValue;
            
            $html .= '<div class="radio-item">';
            $html .= '<input type="radio"';
            $html .= ' id="' . htmlspecialchars($radioId) . '"';
            $html .= ' name="' . htmlspecialchars($name) . '"';
            $html .= ' value="' . htmlspecialchars((string)$choiceValue) . '"';
            
            // Sélectionner si la valeur correspond
            if ((string)$choiceValue === (string)$value) {
                $html .= ' checked';
            }
            
            if ($required) {
                $html .= ' required';
            }

            // Attributs HTML
            foreach ($attr as $key => $val) {
                $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
            }

            $html .= '>';

            // Label pour ce bouton radio
            $html .= '<label for="' . htmlspecialchars($radioId) . '">';
            $html .= htmlspecialchars($choiceLabel);
            $html .= '</label>';
            $html .= '</div>';
        }

        $html .= '</div>';

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
        $choices = $options['choices'] ?? [];

        $html = '';
        foreach ($choices as $choiceValue => $choiceLabel) {
            $html .= '<label class="inline-flex items-center mr-4">';
            $html .= '<input type="radio"';
            $html .= ' name="' . htmlspecialchars($name) . '"';
            $html .= ' value="' . htmlspecialchars((string)$choiceValue) . '"';
            if ((string)$choiceValue === (string)$value) {
                $html .= ' checked';
            }
            if ($required) {
                $html .= ' required';
            }
            foreach ($attr as $key => $val) {
                $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
            }
            $html .= '>';
            $html .= '<span class="ml-2">' . htmlspecialchars($choiceLabel) . '</span>';
            $html .= '</label>';
        }
        return $html;
    }
}

