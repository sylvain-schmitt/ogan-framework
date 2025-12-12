<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 👁️ FORMVIEW - Vue d'un Formulaire (pour le Rendu)
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Représente un formulaire pour le rendu dans les vues.
 * Permet d'accéder aux champs et de les rendre.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form;

class FormView
{
    /**
     * @var FormBuilder FormBuilder associé
     */
    private FormBuilder $formBuilder;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __construct(FormBuilder $formBuilder)
    {
        $this->formBuilder = $formBuilder;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ACCÉDER À UN CHAMP (ArrayAccess)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Permet d'utiliser $form['name'] pour accéder à un champ
     * 
     * @param string $name Nom du champ
     * @return FieldView Vue du champ
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __get(string $name): FieldView
    {
        return new FieldView($name, $this->formBuilder);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RENDRE LE FORMULAIRE COMPLET
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return string HTML du formulaire
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function render(): string
    {
        $options = $this->formBuilder->getOptions();
        $method = $options['method'] ?? 'POST';
        $action = $options['action'] ?? '';
        $attr = $options['attr'] ?? [];

        // Vérifier si le formulaire contient un FileType
        $hasFileType = $this->hasFileType();

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

        // Rendre tous les champs
        foreach ($this->formBuilder->getFields() as $name => $field) {
            $fieldView = new FieldView($name, $this->formBuilder);
            $html .= $fieldView->render();
        }

        $html .= '</form>';

        return $html;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RENDRE LE FORMULAIRE (magic method)
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LES ERREURS
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getErrors(): array
    {
        return $this->formBuilder->getErrors();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI LE FORMULAIRE CONTIENT UN FILETYPE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return bool
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function hasFileType(): bool
    {
        $fields = $this->formBuilder->getFields();
        foreach ($fields as $field) {
            if ($field['type'] === \Ogan\Form\Types\FileType::class) {
                return true;
            }
        }
        return false;
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 👁️ FIELDVIEW - Vue d'un Champ de Formulaire
 * ═══════════════════════════════════════════════════════════════════════
 */
class FieldView
{
    private string $name;
    private FormBuilder $formBuilder;

    public function __construct(string $name, FormBuilder $formBuilder)
    {
        $this->name = $name;
        $this->formBuilder = $formBuilder;
    }

    /**
     * Rendre le champ
     */
    public function render(): string
    {
        $fields = $this->formBuilder->getFields();
        $field = $fields[$this->name] ?? null;

        if (!$field) {
            return '';
        }

        $type = $field['type'];
        $options = $field['options'];
        $data = $this->formBuilder->getData();
        $value = $data[$this->name] ?? $options['data'] ?? '';
        $errors = $this->formBuilder->getErrors();
        $fieldErrors = $errors[$this->name] ?? [];

        // Instancier le type et rendre le champ
        $typeInstance = new $type();
        return $typeInstance->render($this->name, $value, $options, $fieldErrors);
    }

    /**
     * Magic method pour le rendu
     */
    public function __toString(): string
    {
        return $this->render();
    }
}

