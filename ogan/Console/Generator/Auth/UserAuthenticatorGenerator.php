<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 USER AUTHENTICATOR GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class UserAuthenticatorGenerator extends AbstractGenerator
{
    public function generate(string $projectRoot, bool $force = false): array
    {
        $generated = [];
        $skipped = [];

        $path = $projectRoot . '/src/Security/UserAuthenticator.php';
        $this->ensureDirectory(dirname($path));

        if (!$this->fileExists($path) || $force) {
            $this->writeFile($path, $this->getTemplate());
            $generated[] = 'src/Security/UserAuthenticator.php';
        } else {
            $skipped[] = 'src/Security/UserAuthenticator.php (existe déjà)';
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getTemplate(): string
    {
        return <<<'PHP'
<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 USER AUTHENTICATOR
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Gère la logique d'authentification (inscription, connexion, déconnexion).
 * Sépare la logique métier des contrôleurs (principe SOLID).
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\Security;

use App\Model\User;
use Ogan\Security\PasswordHasher;
use Ogan\Session\SessionInterface;

class UserAuthenticator
{
    private PasswordHasher $hasher;

    public function __construct()
    {
        $this->hasher = new PasswordHasher();
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(array $data, bool $autoVerify = true): User
    {
        $user = new User();
        $user->setName($data['name']);
        $user->setEmail($data['email']);
        $user->setPassword($this->hasher->hash($data['password']));
        $user->setRoles(['ROLE_USER']);
        
        if ($autoVerify) {
            $user->setEmailVerifiedAt(date('Y-m-d H:i:s'));
        }
        
        $user->save();

        return $user;
    }

    /**
     * Connexion d'un utilisateur (session)
     */
    public function login(User $user, SessionInterface $session): void
    {
        $session->regenerate();
        $session->set('_auth_user_id', $user->getId());
        $session->set('_auth_user_roles', $user->getRoles());
        $session->set('_auth_user_name', $user->getName());
        $session->set('_auth_user_email', $user->getEmail());
    }

    /**
     * Tentative de connexion avec identifiants
     */
    public function attempt(string $email, string $password): ?User
    {
        $user = User::findByEmail($email);

        if (!$user) {
            return null;
        }

        if (!$this->hasher->verify($password, $user->getPassword())) {
            return null;
        }

        return $user;
    }

    /**
     * Déconnexion d'un utilisateur
     */
    public function logout(SessionInterface $session): void
    {
        $session->remove('_auth_user_id');
        $session->remove('_auth_user_roles');
        $session->remove('_auth_user_name');
        $session->remove('_auth_user_email');
        $session->regenerate();
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    public function isLoggedIn(SessionInterface $session): bool
    {
        return $session->has('_auth_user_id');
    }

    /**
     * Récupère l'utilisateur courant
     */
    public function getUser(SessionInterface $session): ?User
    {
        $userId = $session->get('_auth_user_id');
        
        if (!$userId) {
            return null;
        }

        return User::find($userId);
    }
}
PHP;
    }
}
