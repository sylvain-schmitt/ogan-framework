<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📝 FORMTYPEINTERFACE - Interface pour les Types de Formulaires
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Interface que tous les types de formulaires doivent implémenter.
 * Définit le contrat pour construire un formulaire.
 * 
 * INSPIRATION :
 * -------------
 * Inspiré de Symfony\Component\Form\FormTypeInterface
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form;

interface FormTypeInterface
{
    /**
     * Construit le formulaire en ajoutant des champs au FormBuilder
     * 
     * @param FormBuilder $builder Constructeur de formulaire
     * @param array $options Options du formulaire
     */
    public function buildForm(FormBuilder $builder, array $options): void;

    /**
     * Définit les options par défaut du formulaire
     * 
     * @return array Options par défaut
     */
    public function getDefaultOptions(): array;
}

