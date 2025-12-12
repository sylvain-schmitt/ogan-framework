<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📎 FILETYPE - Type de Champ Upload de Fichier
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Génère un champ d'upload de fichier (<input type="file">).
 * 
 * EXEMPLE D'UTILISATION :
 * ------------------------
 * 
 * ->add('avatar', FileType::class, [
 *     'label' => 'Photo de profil',
 *     'accept' => 'image/*',
 *     'required' => false
 * ])
 * 
 * NOTE IMPORTANTE :
 * -----------------
 * Pour que l'upload fonctionne, le formulaire doit avoir :
 * - method="POST"
 * - enctype="multipart/form-data"
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form\Types;

class FileType implements FieldTypeInterface
{
    public function render(string $name, mixed $value, array $options, array $errors): string
    {
        $label = $options['label'] ?? ucfirst($name);
        $required = $options['required'] ?? false;
        $attr = $options['attr'] ?? [];
        $accept = $options['accept'] ?? null; // Ex: 'image/*', '.pdf,.doc'
        $multiple = $options['multiple'] ?? false;

        $html = '<div class="form-group">';
        $html .= '<label for="' . htmlspecialchars($name) . '">' . htmlspecialchars($label);
        if ($required) {
            $html .= ' <span class="required">*</span>';
        }
        $html .= '</label>';

        $html .= '<input type="file"';
        $html .= ' id="' . htmlspecialchars($name) . '"';
        $html .= ' name="' . htmlspecialchars($name) . ($multiple ? '[]' : '') . '"';
        if ($required) {
            $html .= ' required';
        }
        if ($multiple) {
            $html .= ' multiple';
        }
        if ($accept) {
            $html .= ' accept="' . htmlspecialchars($accept) . '"';
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

        $html = '<input type="file"';
        $html .= ' id="' . htmlspecialchars($name) . '"';
        $html .= ' name="' . htmlspecialchars($name) . '"';
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

