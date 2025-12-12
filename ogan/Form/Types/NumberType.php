<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔢 NUMBERTYPE - Type de Champ Nombre
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Génère un champ numérique (<input type="number">).
 * 
 * EXEMPLE D'UTILISATION :
 * ------------------------
 * 
 * ->add('age', NumberType::class, [
 *     'label' => 'Âge',
 *     'required' => true,
 *     'min' => 0,
 *     'max' => 120,
 *     'step' => 1
 * ])
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Types;

class NumberType implements FieldTypeInterface
{
    public function render(string $name, mixed $value, array $options, array $errors): string
    {
        $label = $options['label'] ?? ucfirst($name);
        $required = $options['required'] ?? false;
        $attr = $options['attr'] ?? [];
        $min = $options['min'] ?? null;
        $max = $options['max'] ?? null;
        $step = $options['step'] ?? null;
        $placeholder = $options['placeholder'] ?? '';

        $html = '<div class="form-group">';
        $html .= '<label for="' . htmlspecialchars($name) . '">' . htmlspecialchars($label);
        if ($required) {
            $html .= ' <span class="required">*</span>';
        }
        $html .= '</label>';

        $html .= '<input type="number"';
        $html .= ' id="' . htmlspecialchars($name) . '"';
        $html .= ' name="' . htmlspecialchars($name) . '"';
        if ($value !== null && $value !== '') {
            $html .= ' value="' . htmlspecialchars((string)$value) . '"';
        }
        if ($required) {
            $html .= ' required';
        }
        if ($min !== null) {
            $html .= ' min="' . htmlspecialchars((string)$min) . '"';
        }
        if ($max !== null) {
            $html .= ' max="' . htmlspecialchars((string)$max) . '"';
        }
        if ($step !== null) {
            $html .= ' step="' . htmlspecialchars((string)$step) . '"';
        }
        if ($placeholder) {
            $html .= ' placeholder="' . htmlspecialchars($placeholder) . '"';
        }

        // Attributs HTML
        foreach ($attr as $key => $val) {
            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
        }

        $html .= '>';

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
        $defaultClass = 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent';
        $inputClass = $attr['class'] ?? $defaultClass;

        $html = '<input type="number"';
        $html .= ' id="' . htmlspecialchars($name) . '"';
        $html .= ' name="' . htmlspecialchars($name) . '"';
        $html .= ' value="' . htmlspecialchars((string)$value) . '"';
        $html .= ' class="' . htmlspecialchars($inputClass) . '"';
        if ($required) {
            $html .= ' required';
        }
        foreach ($attr as $key => $val) {
            if ($key !== 'class') {
                $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
            }
        }
        $html .= '>';
        return $html;
    }
}

