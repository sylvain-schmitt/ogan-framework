<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🏭 FORMFACTORY - Factory pour Créer des Formulaires
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Factory pour créer des formulaires facilement.
 * Simplifie l'utilisation dans les contrôleurs.
 * 
 * EXEMPLE :
 * ---------
 * 
 * $form = $this->formFactory->create(UserFormType::class);
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form;

use Ogan\Validation\Validator;

class FormFactory
{
    private ?Validator $validator;

    public function __construct(?Validator $validator = null)
    {
        $this->validator = $validator;
    }

    /**
     * Créer un formulaire à partir d'un FormType
     * 
     * @param string $type Classe du FormType
     * @param array $options Options du formulaire
     * @return FormBuilder
     */
    public function create(string $type, array $options = []): FormBuilder
    {
        $builder = new FormBuilder($this->validator);
        $builder->setOptions($options);

        // Instancier le type et construire le formulaire
        $formType = new $type();
        $defaultOptions = $formType->getDefaultOptions();
        $mergedOptions = array_merge($defaultOptions, $options);
        
        $formType->buildForm($builder, $mergedOptions);

        return $builder;
    }
}

