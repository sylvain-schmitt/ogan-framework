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
use Ogan\Form\Types\CsrfType;
use Ogan\Security\CsrfTokenManager;

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
     * @var bool CSRF protection enabled
     */
    private bool $csrfEnabled = true;

    /**
     * @var string CSRF token identifier
     */
    private string $csrfTokenId = 'form';

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
     * ACTIVER/DÉSACTIVER LA PROTECTION CSRF
     * ═══════════════════════════════════════════════════════════════════
     */
    public function enableCsrf(bool $enabled = true, ?string $tokenId = null): self
    {
        $this->csrfEnabled = $enabled;
        if ($tokenId !== null) {
            $this->csrfTokenId = $tokenId;
        }
        return $this;
    }

    /**
     * Check if CSRF is enabled
     */
    public function isCsrfEnabled(): bool
    {
        return $this->csrfEnabled;
    }

    /**
     * Get CSRF token ID
     */
    public function getCsrfTokenId(): string
    {
        return $this->csrfTokenId;
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

        // Validate CSRF token first
        if ($this->csrfEnabled) {
            $csrfToken = $request->post('_csrf_token', '');
            $csrfManager = new CsrfTokenManager();
            
            if (!$csrfManager->isTokenValid($this->csrfTokenId, $csrfToken)) {
                $this->errors['_csrf_token'] = ['Token CSRF invalide. Veuillez réessayer.'];
                $this->valid = false;
                return $this;
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
     * Uses the new constraint-based validation system.
     * Constraints are defined in FormType fields via the 'constraints' option.
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function validate(): void
    {
        $this->errors = [];

        foreach ($this->fields as $name => $field) {
            $fieldOptions = $field['options'];
            $value = $this->data[$name] ?? null;

            // Get constraints from field options
            $constraints = $fieldOptions['constraints'] ?? [];

            // Execute each constraint
            foreach ($constraints as $constraint) {
                if (!$constraint instanceof \Ogan\Form\Constraint\ConstraintInterface) {
                    continue;
                }

                // Pass all form data as context (for cross-field validation like EqualTo)
                $error = $constraint->validate($value, $this->data);

                if ($error !== null) {
                    if (!isset($this->errors[$name])) {
                        $this->errors[$name] = [];
                    }
                    $this->errors[$name][] = $error;
                    break; // Stop at first error for this field
                }
            }
        }

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
        // Add CSRF field if enabled and not already present
        if ($this->csrfEnabled && !isset($this->fields['_csrf_token'])) {
            $this->add('_csrf_token', CsrfType::class, [
                'token_id' => $this->csrfTokenId
            ]);
        }
        
        return new FormView($this);
    }
}
