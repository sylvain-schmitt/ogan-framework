<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 ISGRANTED ATTRIBUTE - Protéger les routes par autorisation
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Attribut PHP 8 pour protéger les méthodes de contrôleur.
 * Vérifie les permissions avant l'exécution de l'action.
 * 
 * EXEMPLES:
 * ---------
 * // Protection par rôle
 * #[IsGranted('ROLE_ADMIN')]
 * public function adminDashboard() { ... }
 * 
 * // Protection par Voter avec sujet
 * #[IsGranted('edit', subject: 'post')]
 * public function edit(Post $post) { ... }
 * 
 * // Message personnalisé
 * #[IsGranted('ROLE_ADMIN', message: 'Réservé aux administrateurs')]
 * public function admin() { ... }
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class IsGranted
{
    /**
     * @param string $attribute L'attribut à vérifier (ex: 'ROLE_ADMIN', 'edit')
     * @param string|null $subject Le nom du paramètre de route à utiliser comme sujet
     * @param string $message Message d'erreur personnalisé
     * @param int $statusCode Code HTTP en cas de refus (403 par défaut)
     */
    public function __construct(
        public string $attribute,
        public ?string $subject = null,
        public string $message = 'Access Denied.',
        public int $statusCode = 403
    ) {}
}
