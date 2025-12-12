<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📅 DATETYPE - Type de Champ Date
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Génère un champ de date (<input type="date">).
 * 
 * EXEMPLE D'UTILISATION :
 * ------------------------
 * 
 * ->add('birthdate', DateType::class, [
 *     'label' => 'Date de naissance',
 *     'required' => true,
 *     'min' => '1900-01-01',
 *     'max' => 'today'
 * ])
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Types;

class DateType implements FieldTypeInterface
{
    public function render(string $name, mixed $value, array $options, array $errors): string
    {
        $label = $options['label'] ?? ucfirst($name);
        $required = $options['required'] ?? false;
        $attr = $options['attr'] ?? [];
        $min = $options['min'] ?? null;
        $max = $options['max'] ?? null;

        // Convertir 'today' en date actuelle
        if ($max === 'today') {
            $max = date('Y-m-d');
        }

        // Formater la valeur si c'est un DateTime
        if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d');
        } elseif ($value !== null && $value !== '') {
            $value = (string)$value;
        }

        $html = '<div class="form-group">';
        $html .= '<label for="' . htmlspecialchars($name) . '">' . htmlspecialchars($label);
        if ($required) {
            $html .= ' <span class="required">*</span>';
        }
        $html .= '</label>';

        $html .= '<input type="date"';
        $html .= ' id="' . htmlspecialchars($name) . '"';
        $html .= ' name="' . htmlspecialchars($name) . '"';
        if ($value) {
            $html .= ' value="' . htmlspecialchars($value) . '"';
        }
        if ($required) {
            $html .= ' required';
        }
        if ($min) {
            $html .= ' min="' . htmlspecialchars($min) . '"';
        }
        if ($max) {
            $html .= ' max="' . htmlspecialchars($max) . '"';
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

        $html = '<input type="date"';
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

