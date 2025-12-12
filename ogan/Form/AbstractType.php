<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📝 ABSTRACTTYPE - Classe de Base pour les Types de Formulaires
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Classe abstraite de base pour tous les types de formulaires.
 * Fournit une implémentation par défaut de FormTypeInterface.
 * 
 * EXEMPLE D'UTILISATION :
 * ------------------------
 * 
 * class UserFormType extends AbstractType {
 *     public function buildForm(FormBuilder $builder, array $options): void {
 *         $builder
 *             ->add('name', TextType::class)
 *             ->add('email', EmailType::class);
 *     }
 * }
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Form;

abstract class AbstractType implements FormTypeInterface
{
    /**
     * Construit le formulaire
     * 
     * À implémenter dans les classes filles
     */
    abstract public function buildForm(FormBuilder $builder, array $options): void;

    /**
     * Options par défaut
     * 
     * Peut être surchargé dans les classes filles
     */
    public function getDefaultOptions(): array
    {
        return [
            'method' => 'POST',
            'action' => '',
            'attr' => [],
        ];
    }
}

