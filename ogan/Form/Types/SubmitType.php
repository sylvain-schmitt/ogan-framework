<?php

namespace Ogan\Form\Types;

class SubmitType implements FieldTypeInterface
{
    public function render(string $name, mixed $value, array $options, array $errors): string
    {
        $label = $options['label'] ?? 'Envoyer';
        $attr = $options['attr'] ?? [];

        // Classes par défaut Tailwind (peuvent être surchargées via attr)
        $defaultClass = 'bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors';
        $buttonClass = $attr['class'] ?? $defaultClass;
        
        $html = '<div class="mt-6">';
        $html .= '<button type="submit"';
        $html .= ' name="' . htmlspecialchars($name) . '"';
        $html .= ' class="' . htmlspecialchars($buttonClass) . '"';

        // Attributs HTML (sauf class qui est déjà géré)
        foreach ($attr as $key => $val) {
            if ($key !== 'class') {
                $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
            }
        }

        $html .= '>' . htmlspecialchars($label) . '</button>';
        $html .= '</div>';

        return $html;
    }

    public function renderWidget(string $name, mixed $value, array $options): string
    {
        $label = $options['label'] ?? ucfirst($name);
        $attr = $options['attr'] ?? [];
        $defaultClass = 'bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline';
        $buttonClass = $attr['class'] ?? $defaultClass;

        $html = '<button type="submit"';
        $html .= ' class="' . htmlspecialchars($buttonClass) . '"';
        foreach ($attr as $key => $val) {
            if ($key !== 'class') {
                $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
            }
        }
        $html .= '>' . htmlspecialchars($label) . '</button>';
        return $html;
    }
}

