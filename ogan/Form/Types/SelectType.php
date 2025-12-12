<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📋 SELECTTYPE - Type de Champ Liste Déroulante
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Génère un élément <select> HTML avec des options.
 * 
 * EXEMPLE D'UTILISATION :
 * ------------------------
 * 
 * ->add('country', SelectType::class, [
 *     'label' => 'Pays',
 *     'choices' => [
 *         'fr' => 'France',
 *         'us' => 'États-Unis',
 *         'uk' => 'Royaume-Uni'
 *     ],
 *     'required' => true
 * ])
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Types;

class SelectType implements FieldTypeInterface
{
    public function render(string $name, mixed $value, array $options, array $errors): string
    {
        $label = $options['label'] ?? ucfirst($name);
        $required = $options['required'] ?? false;
        $attr = $options['attr'] ?? [];
        $choices = $options['choices'] ?? [];
        $placeholder = $options['placeholder'] ?? null;
        $multiple = $options['multiple'] ?? false;

        $html = '<div class="form-group">';
        $html .= '<label for="' . htmlspecialchars($name) . '">' . htmlspecialchars($label);
        if ($required) {
            $html .= ' <span class="required">*</span>';
        }
        $html .= '</label>';

        $html .= '<select';
        $html .= ' id="' . htmlspecialchars($name) . '"';
        $html .= ' name="' . htmlspecialchars($name) . ($multiple ? '[]' : '') . '"';
        if ($required) {
            $html .= ' required';
        }
        if ($multiple) {
            $html .= ' multiple';
        }

        // Attributs HTML
        foreach ($attr as $key => $val) {
            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
        }

        $html .= '>';

        // Option placeholder (si défini)
        if ($placeholder !== null) {
            $html .= '<option value="">' . htmlspecialchars($placeholder) . '</option>';
        }

        // Options
        foreach ($choices as $choiceValue => $choiceLabel) {
            $html .= '<option value="' . htmlspecialchars((string)$choiceValue) . '"';
            
            // Sélectionner l'option si elle correspond à la valeur
            if ($multiple) {
                // Pour les selects multiples, la valeur est un tableau
                $selectedValues = is_array($value) ? $value : [];
                if (in_array($choiceValue, $selectedValues)) {
                    $html .= ' selected';
                }
            } else {
                if ((string)$choiceValue === (string)$value) {
                    $html .= ' selected';
                }
            }
            
            $html .= '>' . htmlspecialchars($choiceLabel) . '</option>';
        }

        $html .= '</select>';

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
        $placeholder = $options['placeholder'] ?? null;
        $multiple = $options['multiple'] ?? false;
        $defaultClass = 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent';
        $inputClass = $attr['class'] ?? $defaultClass;

        $html = '<select';
        $html .= ' id="' . htmlspecialchars($name) . '"';
        $html .= ' name="' . htmlspecialchars($name) . ($multiple ? '[]' : '') . '"';
        $html .= ' class="' . htmlspecialchars($inputClass) . '"';
        if ($required) {
            $html .= ' required';
        }
        if ($multiple) {
            $html .= ' multiple';
        }
        foreach ($attr as $key => $val) {
            if ($key !== 'class') {
                $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
            }
        }
        $html .= '>';

        if ($placeholder !== null) {
            $html .= '<option value="">' . htmlspecialchars($placeholder) . '</option>';
        }

        foreach ($choices as $choiceValue => $choiceLabel) {
            $html .= '<option value="' . htmlspecialchars((string)$choiceValue) . '"';
            if ($multiple) {
                $selectedValues = is_array($value) ? $value : [];
                if (in_array($choiceValue, $selectedValues)) {
                    $html .= ' selected';
                }
            } else {
                if ((string)$choiceValue === (string)$value) {
                    $html .= ' selected';
                }
            }
            $html .= '>' . htmlspecialchars($choiceLabel) . '</option>';
        }

        $html .= '</select>';
        return $html;
    }
}

