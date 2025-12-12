<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔨 FORMBUILDER - Constructeur de Formulaires
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Construit un formulaire à partir d'un FormType.
 * Permet d'ajouter des champs de manière fluide.
 * 
 * EXEMPLE D'UTILISATION :
 * ------------------------
 * 
 * $builder = new FormBuilder();
 * $builder
 *     ->add('name', TextType::class, ['label' => 'Nom'])
 *     ->add('email', EmailType::class);
 * 
 * $form = $builder->getForm();
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form;

use Ogan\Validation\Validator;

class FormBuilder
{
    /**
     * @var array Champs du formulaire
     */
    private array $fields = [];

    /**
     * @var array Options du formulaire
     */
    private array $options = [];

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LES OPTIONS
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @var array Données du formulaire
     */
    private array $data = [];

    /**
     * @var array Erreurs de validation
     */
    private array $errors = [];

    /**
     * @var bool Indique si le formulaire a été soumis
     */
    private bool $submitted = false;

    /**
     * @var bool Indique si le formulaire est valide
     */
    private bool $valid = false;

    /**
     * @var Validator Validateur
     */
    private Validator $validator;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __construct(?Validator $validator = null)
    {
        $this->validator = $validator ?? new Validator();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AJOUTER UN CHAMP AU FORMULAIRE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $name Nom du champ
     * @param string $type Type du champ (classe)
     * @param array $options Options du champ
     * @return self Pour le chaînage
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function add(string $name, string $type, array $options = []): self
    {
        $this->fields[$name] = [
            'type' => $type,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉFINIR LES OPTIONS DU FORMULAIRE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param array $options Options
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * TRAITER LA REQUÊTE (remplir le formulaire avec les données POST)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param \Ogan\Http\RequestInterface $request Requête HTTP
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function handleRequest(\Ogan\Http\RequestInterface $request): self
    {
        $this->submitted = true;

        // Récupérer les données POST
        foreach ($this->fields as $name => $field) {
            // Pour les FileType, on récupère depuis $_FILES
            if ($field['type'] === \Ogan\Form\Types\FileType::class) {
                $this->data[$name] = $request->getFile($name);
            } else {
                $this->data[$name] = $request->post($name, '');
            }
        }

        // Valider le formulaire
        $this->validate();

        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VALIDER LE FORMULAIRE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function validate(): void
    {
        $rules = [];

        // Construire les règles de validation depuis les options des champs
        foreach ($this->fields as $name => $field) {
            $fieldOptions = $field['options'];
            $fieldRules = [];

            if ($fieldOptions['required'] ?? false) {
                $fieldRules[] = 'required';
            }

            // Règles spécifiques selon le type
            $type = $field['type'];
            if ($type === \Ogan\Form\Types\EmailType::class) {
                $fieldRules[] = 'email';
            }

            if (isset($fieldOptions['min'])) {
                $fieldRules[] = 'min:' . $fieldOptions['min'];
            }

            if (isset($fieldOptions['max'])) {
                $fieldRules[] = 'max:' . $fieldOptions['max'];
            }

            if (!empty($fieldRules)) {
                $rules[$name] = implode('|', $fieldRules);
            }
        }

        // Valider
        $this->errors = $this->validator->validate($this->data, $rules);
        $this->valid = empty($this->errors);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI LE FORMULAIRE EST VALIDE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return bool
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI LE FORMULAIRE A ÉTÉ SOUMIS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return bool
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function isSubmitted(): bool
    {
        return $this->submitted;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉFINIR LES DONNÉES DU FORMULAIRE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Permet de pré-remplir le formulaire avec des données
     * 
     * @param array $data Données à définir
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function setData(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LES DONNÉES DU FORMULAIRE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return array
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AJOUTER UNE ERREUR MANUELLEMENT
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Permet d'ajouter une erreur personnalisée après validation
     * 
     * @param string $field Nom du champ
     * @param string $message Message d'erreur
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
        $this->valid = false;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LES ERREURS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return array
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LES CHAMPS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return array
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CRÉER LA VUE DU FORMULAIRE (pour le rendu)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return FormView
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function createView(): FormView
    {
        return new FormView($this);
    }
}
