<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📝 FORMHELPER - Helpers pour le Rendu de Formulaires
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Fournit des helpers pour simplifier le rendu des formulaires dans les templates.
 * 
 * HELPERS DISPONIBLES :
 * ---------------------
 * - formStart()  : Ouvre le formulaire (\<form\>)
 * - formEnd()    : Ferme le formulaire (\</form\>)
 * - formRow()    : Affiche label + input + erreurs
 * - formLabel()  : Affiche uniquement le label
 * - formWidget() : Affiche uniquement l'input
 * - formErrors() : Affiche uniquement les erreurs
 * - formRest()   : Affiche les champs restants non rendus
 * 
 * USAGE :
 * -------
 * {% form_start(form) %}
 *   {% form_row(form.name) %}
 *   {% form_row(form.email) %}
 *   <button type="submit">Envoyer</button>
 * {% form_end(form) %}
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\View\Helper;

use Ogan\Form\FormView;
use Ogan\Form\FieldView;
use Ogan\Form\FormBuilder;

class FormHelper
{
    /**
     * Champs déjà rendus (pour form_rest)
     */
    private array $renderedFields = [];

    /**
     * ═══════════════════════════════════════════════════════════════════
     * FORM_START - Ouvre le formulaire
     * ═══════════════════════════════════════════════════════════════════
     */
    public function formStart(FormView $form, array $options = []): string
    {
        // Réinitialiser les champs rendus
        $this->renderedFields = [];
        
        $formBuilder = $this->getFormBuilder($form);
        $formOptions = $formBuilder->getOptions();
        
        $method = $options['method'] ?? $formOptions['method'] ?? 'POST';
        $action = $options['action'] ?? $formOptions['action'] ?? '';
        $attr = array_merge($formOptions['attr'] ?? [], $options['attr'] ?? []);
        
        // Vérifier si le formulaire contient un FileType
        $hasFileType = $this->hasFileType($formBuilder);
        
        $html = '<form method="' . htmlspecialchars($method) . '"';
        
        if ($action) {
            $html .= ' action="' . htmlspecialchars($action) . '"';
        }
        
        // Ajouter enctype="multipart/form-data" si nécessaire
        if ($hasFileType && !isset($attr['enctype'])) {
            $html .= ' enctype="multipart/form-data"';
        }
        
        // Attributs HTML
        foreach ($attr as $key => $value) {
            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        
        $html .= '>';
        
        return $html;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * FORM_END - Ferme le formulaire
     * ═══════════════════════════════════════════════════════════════════
     */
    public function formEnd(FormView $form): string
    {
        return '</form>';
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * FORM_ROW - Affiche label + input + erreurs (avec Tailwind)
     * ═══════════════════════════════════════════════════════════════════
     */
    public function formRow(FieldView $field, array $options = []): string
    {
        $fieldName = $this->getFieldName($field);
        $this->renderedFields[] = $fieldName;
        
        $html = '<div class="mb-4">';
        $html .= $this->formLabel($field);
        $html .= $this->formWidget($field, $options);
        $html .= $this->formErrors($field);
        $html .= '</div>';
        
        return $html;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * FORM_LABEL - Affiche uniquement le label
     * ═══════════════════════════════════════════════════════════════════
     */
    public function formLabel(FieldView $field, ?string $label = null): string
    {
        $fieldName = $this->getFieldName($field);
        $formBuilder = $this->getFormBuilderFromField($field);
        $fields = $formBuilder->getFields();
        $fieldData = $fields[$fieldName] ?? null;
        
        if (!$fieldData) {
            return '';
        }
        
        $options = $fieldData['options'];
        $labelText = $label ?? $options['label'] ?? ucfirst($fieldName);
        
        if ($labelText === false) {
            return '';
        }
        
        $required = $options['required'] ?? false;
        $requiredMark = $required ? '<span class="text-red-500">*</span>' : '';
        
        return '<label for="' . htmlspecialchars($fieldName) . '" class="block text-gray-700 text-sm font-bold mb-2">'
            . htmlspecialchars($labelText) . $requiredMark
            . '</label>';
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * FORM_WIDGET - Affiche uniquement l'input
     * ═══════════════════════════════════════════════════════════════════
     */
    public function formWidget(FieldView $field, array $options = []): string
    {
        $fieldName = $this->getFieldName($field);
        $formBuilder = $this->getFormBuilderFromField($field);
        $fields = $formBuilder->getFields();
        $fieldData = $fields[$fieldName] ?? null;
        
        if (!$fieldData) {
            return '';
        }
        
        $type = $fieldData['type'];
        $fieldOptions = array_merge($fieldData['options'], $options);
        $data = $formBuilder->getData();
        $value = $data[$fieldName] ?? $fieldOptions['data'] ?? '';
        
        // Ajouter les classes Tailwind par défaut
        if (!isset($fieldOptions['attr']['class'])) {
            $fieldOptions['attr']['class'] = 'shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline';
        }
        
        // Instancier le type et rendre le widget
        $typeInstance = new $type();
        return $typeInstance->renderWidget($fieldName, $value, $fieldOptions);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * FORM_ERRORS - Affiche uniquement les erreurs
     * ═══════════════════════════════════════════════════════════════════
     */
    public function formErrors(FieldView $field): string
    {
        $fieldName = $this->getFieldName($field);
        $formBuilder = $this->getFormBuilderFromField($field);
        $errors = $formBuilder->getErrors();
        $fieldErrors = $errors[$fieldName] ?? [];
        
        if (empty($fieldErrors)) {
            return '';
        }
        
        $html = '';
        foreach ($fieldErrors as $error) {
            $html .= '<p class="text-red-500 text-xs italic mt-1">' . htmlspecialchars($error) . '</p>';
        }
        
        return $html;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * FORM_REST - Affiche les champs restants non rendus
     * ═══════════════════════════════════════════════════════════════════
     */
    public function formRest(FormView $form): string
    {
        $formBuilder = $this->getFormBuilder($form);
        $fields = $formBuilder->getFields();
        
        $html = '';
        foreach ($fields as $name => $field) {
            if (!in_array($name, $this->renderedFields)) {
                $fieldView = $form->$name;
                $html .= $this->formRow($fieldView);
            }
        }
        
        return $html;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * HELPERS PRIVÉS
     * ═══════════════════════════════════════════════════════════════════
     */

    private function getFormBuilder(FormView $form): FormBuilder
    {
        $reflection = new \ReflectionClass($form);
        $property = $reflection->getProperty('formBuilder');
        $property->setAccessible(true);
        return $property->getValue($form);
    }

    private function getFormBuilderFromField(FieldView $field): FormBuilder
    {
        $reflection = new \ReflectionClass($field);
        $property = $reflection->getProperty('formBuilder');
        $property->setAccessible(true);
        return $property->getValue($field);
    }

    private function getFieldName(FieldView $field): string
    {
        $reflection = new \ReflectionClass($field);
        $property = $reflection->getProperty('name');
        $property->setAccessible(true);
        return $property->getValue($field);
    }

    private function hasFileType(FormBuilder $formBuilder): bool
    {
        $fields = $formBuilder->getFields();
        foreach ($fields as $field) {
            if ($field['type'] === \Ogan\Form\Types\FileType::class) {
                return true;
            }
        }
        return false;
    }
}
