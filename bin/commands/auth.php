<?php

use Ogan\Database\Database;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 COMMANDES AUTH - Génération du système d'authentification
 * ═══════════════════════════════════════════════════════════════════════
 */
function registerAuthCommands($app) {
    $projectRoot = dirname(__DIR__, 2);

    // make:auth
    $app->addCommand('make:auth', function($args) use ($projectRoot) {
        $force = in_array('--force', $args);
        
        echo "🔐 Génération du système d'authentification...\n\n";

        $generated = [];
        $skipped = [];

        // 1. Model User
        $userModelPath = $projectRoot . '/src/Model/User.php';
        if (!file_exists($userModelPath) || $force) {
            generateUserModel($userModelPath);
            $generated[] = 'src/Model/User.php';
        } else {
            $skipped[] = 'src/Model/User.php (existe déjà)';
        }

        // 2. SecurityController
        $securityControllerPath = $projectRoot . '/src/Controller/SecurityController.php';
        if (!file_exists($securityControllerPath) || $force) {
            generateSecurityController($securityControllerPath);
            $generated[] = 'src/Controller/SecurityController.php';
        } else {
            $skipped[] = 'src/Controller/SecurityController.php (existe déjà)';
        }

        // 3. FormTypes
        $formsDir = $projectRoot . '/src/Form';
        if (!is_dir($formsDir)) {
            mkdir($formsDir, 0755, true);
        }

        $forms = [
            'LoginFormType.php' => 'generateLoginFormType',
            'RegisterFormType.php' => 'generateRegisterFormType',
            'ForgotPasswordFormType.php' => 'generateForgotPasswordFormType',
            'ResetPasswordFormType.php' => 'generateResetPasswordFormType',
        ];

        foreach ($forms as $filename => $generator) {
            $path = $formsDir . '/' . $filename;
            if (!file_exists($path) || $force) {
                $generator($path);
                $generated[] = "src/Form/{$filename}";
            } else {
                $skipped[] = "src/Form/{$filename} (existe déjà)";
            }
        }

        // 3b. UserAuthenticator
        $securityDir = $projectRoot . '/src/Security';
        if (!is_dir($securityDir)) {
            mkdir($securityDir, 0755, true);
        }

        $authenticatorPath = $securityDir . '/UserAuthenticator.php';
        if (!file_exists($authenticatorPath) || $force) {
            generateUserAuthenticator($authenticatorPath);
            $generated[] = 'src/Security/UserAuthenticator.php';
        } else {
            $skipped[] = 'src/Security/UserAuthenticator.php (existe déjà)';
        }

        // 3c. UserRepository
        $repositoriesPath = $projectRoot . '/src/Repository';
        // Utilisation directe du générateur de repository s'il est disponible via require (sinon on fait une fonction locale)
        // Comme nous sommes dans un fichier inclus, nous n'avons pas accès facile à l'autoloader pour instancier la classe du framework si elle n'est pas chargée.
        // Mais Ogan\Console\Generator\RepositoryGenerator devrait être accessible.
        
        // On va générer le UserRepository manuellement pour être sûr, ou via une fonction locale si on n'a pas accès à la classe.
        // Option simple : fonction locale generateUserRepository
        $userRepoPath = $repositoriesPath . '/UserRepository.php';
        if (!is_dir($repositoriesPath)) {
            mkdir($repositoriesPath, 0755, true);
        }
        
        if(!file_exists($userRepoPath) || $force) {
             generateUserRepository($userRepoPath);
             $generated[] = 'src/Repository/UserRepository.php';
        } else {
             $skipped[] = 'src/Repository/UserRepository.php (existe déjà)';
        }


        // 4. Dashboard (Controller + Templates)
        $dashboardControllerPath = $projectRoot . '/src/Controller/DashboardController.php';
        if (!file_exists($dashboardControllerPath) || $force) {
            generateDashboardController($dashboardControllerPath);
            $generated[] = 'src/Controller/DashboardController.php';
        } else {
            $skipped[] = 'src/Controller/DashboardController.php (existe déjà)';
        }

        $dashboardTemplatesDir = $projectRoot . '/templates/dashboard';
        if (!is_dir($dashboardTemplatesDir)) {
            mkdir($dashboardTemplatesDir, 0755, true);
        }

        $dashboardFiles = [
            'layout.ogan' => 'generateDashboardLayout',
            'index.ogan' => 'generateDashboardIndex',
        ];

        foreach ($dashboardFiles as $filename => $generator) {
            $path = $dashboardTemplatesDir . '/' . $filename;
            if (!file_exists($path) || $force) {
                $generator($path);
                $generated[] = "templates/dashboard/{$filename}";
            } else {
                $skipped[] = "templates/dashboard/{$filename} (existe déjà)";
            }
        }

        // 4b. Dashboard Components
        generateDashboardComponents($projectRoot);
        $generated[] = 'templates/components/dashboard/sidebar.ogan';
        $generated[] = 'templates/components/dashboard/navbar.ogan';

        // 4c. Flashes Component
        $flashesPath = $projectRoot . '/templates/components/flashes.ogan';
        if (!file_exists($flashesPath) || $force) {
            $componentsDir = $projectRoot . '/templates/components';
            if (!is_dir($componentsDir)) {
                mkdir($componentsDir, 0755, true);
            }
            generateFlashesComponent($flashesPath);
            $generated[] = 'templates/components/flashes.ogan';
        } else {
            $skipped[] = 'templates/components/flashes.ogan (existe déjà)';
        }

        // 4d. Profile Templates
        $userTemplatesDir = $projectRoot . '/templates/user';
        if (!is_dir($userTemplatesDir)) {
            mkdir($userTemplatesDir, 0755, true);
        }

        $profilePath = $userTemplatesDir . '/profile.ogan';
        if (!file_exists($profilePath) || $force) {
            generateProfileTemplate($profilePath);
            $generated[] = 'templates/user/profile.ogan';
        } else {
            $skipped[] = 'templates/user/profile.ogan (existe déjà)';
        }

        $profileEditPath = $userTemplatesDir . '/edit.ogan';
        if (!file_exists($profileEditPath) || $force) {
            generateProfileEditTemplate($profileEditPath);
            $generated[] = 'templates/user/edit.ogan';
        } else {
            $skipped[] = 'templates/user/edit.ogan (existe déjà)';
        }

        // 4e. ProfileFormType
        $profileFormPath = $formsDir . '/ProfileFormType.php';
        if (!file_exists($profileFormPath) || $force) {
            generateProfileFormType($profileFormPath);
            $generated[] = 'src/Form/ProfileFormType.php';
        } else {
            $skipped[] = 'src/Form/ProfileFormType.php (existe déjà)';
        }

        // 4f. JS Assets
        $jsDir = $projectRoot . '/public/assets/js';
        if (!is_dir($jsDir)) {
            mkdir($jsDir, 0755, true);
        }

        $themeJsPath = $jsDir . '/theme.js';
        if (!file_exists($themeJsPath) || $force) {
            generateThemeJs($themeJsPath);
            $generated[] = 'public/assets/js/theme.js';
        } else {
            $skipped[] = 'public/assets/js/theme.js (existe déjà)';
        }

        $flashesJsPath = $jsDir . '/flashes.js';
        if (!file_exists($flashesJsPath) || $force) {
            generateFlashesJs($flashesJsPath);
            $generated[] = 'public/assets/js/flashes.js';
        } else {
            $skipped[] = 'public/assets/js/flashes.js (existe déjà)';
        }

        // 5. Templates Security
        $templatesDir = $projectRoot . '/templates/security';
        if (!is_dir($templatesDir)) {
            mkdir($templatesDir, 0755, true);
        }

        $templates = [
            'login.ogan' => 'generateLoginTemplate',
            'register.ogan' => 'generateRegisterTemplate',
            'forgot_password.ogan' => 'generateForgotPasswordTemplate',
            'reset_password.ogan' => 'generateResetPasswordTemplate',
        ];

        foreach ($templates as $filename => $generator) {
            $path = $templatesDir . '/' . $filename;
            if (!file_exists($path) || $force) {
                $generator($path);
                $generated[] = "templates/security/{$filename}";
            } else {
                $skipped[] = "templates/security/{$filename} (existe déjà)";
            }
        }

        // 6. Migrations (jamais régénérées, même avec --force, car ce sont des fichiers de schéma)
        $migrationsDir = $projectRoot . '/database/migrations';
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
        }

        // Migration users - ne jamais recréer si existe
        $existingMigrations = glob($migrationsDir . '/*_create_users_table.php');
        if (empty($existingMigrations)) {
            $timestamp = date('Y_m_d_His');
            generateUsersMigration($migrationsDir . "/{$timestamp}_create_users_table.php");
            $generated[] = "database/migrations/{$timestamp}_create_users_table.php";
        } else {
            $skipped[] = 'Migration users (existe déjà)';
        }

        // Migration remember_tokens - ne jamais recréer si existe
        $existingRememberMigrations = glob($migrationsDir . '/*_create_remember_tokens_table.php');
        if (empty($existingRememberMigrations)) {
            usleep(1000000); // 1 seconde pour timestamp différent
            $timestamp = date('Y_m_d_His');
            generateRememberTokensMigration($migrationsDir . "/{$timestamp}_create_remember_tokens_table.php");
            $generated[] = "database/migrations/{$timestamp}_create_remember_tokens_table.php";
        } else {
            $skipped[] = 'Migration remember_tokens (existe déjà)';
        }

        // Résumé
        echo "✅ Fichiers générés :\n";
        foreach ($generated as $file) {
            echo "   📄 {$file}\n";
        }

        if (!empty($skipped)) {
            echo "\n⏭️  Fichiers ignorés (utilisez --force pour écraser) :\n";
            foreach ($skipped as $file) {
                echo "   ⚠️  {$file}\n";
            }
        }

        echo "\n🎉 Système d'authentification et Dashboard générés avec succès !\n\n";
        echo "📋 Prochaines étapes :\n";
        echo "   1. php bin/console migrate      # Créer les tables\n";
        echo "   2. Configurer MAILER_DSN dans .env\n";
        echo "   3. Accéder à /register pour créer un compte\n";
        echo "   4. Accéder à /dashboard pour voir le back-office\n";

        return 0;
    }, 'Génère le système d\'authentification complet (Auth + Dashboard)');
}

// ═══════════════════════════════════════════════════════════════════════
// GÉNÉRATEURS
// ═══════════════════════════════════════════════════════════════════════

function generateUserModel(string $path): void
{
    $content = <<<'PHP'
<?php

namespace App\Model;

use Ogan\Database\Model;
use Ogan\Security\Auth\UserInterface;

class User extends Model implements UserInterface
{
    protected static ?string $table = 'users';
    protected static ?string $primaryKey = 'id';

    private ?int $id = null;
    private ?string $email = null;
    private ?string $password = null;
    private ?string $name = null;
    private array $roles = ['ROLE_USER'];
    private ?\DateTime $emailVerifiedAt = null;
    private ?\DateTime $createdAt = null;
    private ?\DateTime $updatedAt = null;

    // ─────────────────────────────────────────────────────────────
    // GETTERS
    // ─────────────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        return $this->email ?? '';
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Garantir que tout utilisateur a au moins ROLE_USER
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }
        return array_unique($roles);
    }

    public function getEmailVerifiedAt(): ?\DateTime
    {
        return $this->emailVerifiedAt;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    // ─────────────────────────────────────────────────────────────
    // SETTERS
    // ─────────────────────────────────────────────────────────────

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function addRole(string $role): self
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
        return $this;
    }

    public function setEmailVerifiedAt(?\DateTime $date): self
    {
        $this->emailVerifiedAt = $date;
        return $this;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // ─────────────────────────────────────────────────────────────
    // MÉTHODES DE REQUÊTE
    // ─────────────────────────────────────────────────────────────

    public static function findByEmail(string $email): ?self
    {
        $result = self::where('email', '=', $email)->first();

        if ($result === null) {
            return null;
        }

        $user = new static($result);
        $user->exists = true;
        $user->hydrateFromAttributes();
        return $user;
    }

    public function isVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }
}
PHP;

    file_put_contents($path, $content);
}

function generateSecurityController(string $path): void
{
    $content = <<<'PHP'
<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 SECURITY CONTROLLER
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Simplified authentication controller using UserAuthenticator service
 * and constraint-based form validation.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\Controller;

use Ogan\Controller\AbstractController;
use Ogan\Router\Attributes\Route;
use App\Model\User;
use App\Form\LoginFormType;
use App\Form\RegisterFormType;
use App\Form\ForgotPasswordFormType;
use App\Form\ResetPasswordFormType;
use App\Security\UserAuthenticator;
use Ogan\Security\RememberMeService;

class SecurityController extends AbstractController
{
    private ?UserAuthenticator $auth = null;
    private ?RememberMeService $rememberMe = null;

    private function getAuth(): UserAuthenticator
    {
        if ($this->auth === null) {
            $this->auth = new UserAuthenticator();
        }
        return $this->auth;
    }

    private function getRememberMe(): RememberMeService
    {
        if ($this->rememberMe === null) {
            $this->rememberMe = new RememberMeService();
        }
        return $this->rememberMe;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * 🔐 LOGIN
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/login', methods: ['GET', 'POST'], name: 'security_login')]
    public function login()
    {
        if ($this->getAuth()->isLoggedIn($this->session)) {
            return $this->redirect('/dashboard');
        }

        $form = $this->formFactory->create(LoginFormType::class, [
            'action' => '/login',
            'method' => 'POST'
        ]);

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $user = $this->getAuth()->attempt($data['email'], $data['password']);
                
                if ($user) {
                    $this->getAuth()->login($user, $this->session);
                    
                    // Handle Remember Me
                    if (!empty($data['remember_me'])) {
                        $token = $this->getRememberMe()->createToken($user->getId());
                        $this->getRememberMe()->setCookie($token);
                    }
                    
                    $this->session->setFlash('success', 'Connexion réussie !');
                    return $this->redirect('/dashboard');
                }
                
                $form->addError('email', 'Email ou mot de passe incorrect.');
            }
        }

        return $this->render('security/login.ogan', [
            'title' => 'Connexion',
            'form' => $form->createView()
        ]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * 🚪 LOGOUT
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/logout', methods: ['GET'], name: 'security_logout')]
    public function logout()
    {
        // Clear remember me token if exists
        $token = $this->getRememberMe()->getTokenFromCookie();
        if ($token) {
            $this->getRememberMe()->deleteToken($token);
            $this->getRememberMe()->clearCookie();
        }
        
        $this->getAuth()->logout($this->session);
        $this->session->setFlash('success', 'Vous avez été déconnecté.');
        return $this->redirect('/login');
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * 📝 REGISTER
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/register', methods: ['GET', 'POST'], name: 'security_register')]
    public function register()
    {
        if ($this->getAuth()->isLoggedIn($this->session)) {
            return $this->redirect('/dashboard');
        }

        $form = $this->formFactory->create(RegisterFormType::class, [
            'action' => '/register',
            'method' => 'POST'
        ]);

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                
                // All validation (password match, email unique) is done in FormType
                $user = $this->getAuth()->register($data);
                $this->getAuth()->login($user, $this->session);
                $this->session->setFlash('success', 'Compte créé avec succès !');
                
                return $this->redirect('/dashboard');
            }
        }

        return $this->render('security/register.ogan', [
            'title' => 'Inscription',
            'form' => $form->createView()
        ]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * 🔑 FORGOT PASSWORD
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/forgot-password', methods: ['GET', 'POST'], name: 'security_forgot_password')]
    public function forgotPassword()
    {
        if ($this->getAuth()->isLoggedIn($this->session)) {
            return $this->redirect('/');
        }

        $form = $this->formFactory->create(ForgotPasswordFormType::class, [
            'action' => '/forgot-password',
            'method' => 'POST'
        ]);

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $user = User::findByEmail($data['email']);
                
                if ($user) {
                    // TODO: Send password reset email
                }
                
                // Always show success to prevent email enumeration
                $this->session->setFlash('success', 'If this email exists, a reset link has been sent.');
                return $this->redirect('/login');
            }
        }

        return $this->render('security/forgot_password.ogan', [
            'title' => 'Forgot Password',
            'form' => $form->createView()
        ]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * 🔑 RESET PASSWORD
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/reset-password/{token}', methods: ['GET', 'POST'], name: 'security_reset_password')]
    public function resetPassword(string $token)
    {
        $form = $this->formFactory->create(ResetPasswordFormType::class, [
            'action' => '/reset-password/' . $token,
            'method' => 'POST'
        ]);

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);

            if ($form->isSubmitted() && $form->isValid()) {
                // TODO: Reset password
                $this->session->setFlash('success', 'Password has been reset.');
                return $this->redirect('/login');
            }
        }

        return $this->render('security/reset_password.ogan', [
            'title' => 'Reset Password',
            'form' => $form->createView(),
            'token' => $token
        ]);
    }
}
PHP;

    file_put_contents($path, $content);
}

function generateUserAuthenticator(string $path): void
{
    $content = <<<'PHP'
<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 USER AUTHENTICATOR
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Handles user authentication logic (registration, login, logout).
 * Separates business logic from controllers following SOLID principles.
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
     * Register a new user
     */
    public function register(array $data): User
    {
        $user = new User();
        $user->setName($data['name']);
        $user->setEmail($data['email']);
        $user->setPassword($this->hasher->hash($data['password']));
        $user->setRoles(['ROLE_USER']);
        $user->save();

        return $user;
    }

    /**
     * Login a user (set session)
     */
    public function login(User $user, SessionInterface $session): void
    {
        $session->regenerate();
        $session->set('_auth_user_id', $user->getId());
        $session->set('_auth_user_roles', $user->getRoles());
    }

    /**
     * Attempt login with credentials
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
     * Logout a user
     */
    public function logout(SessionInterface $session): void
    {
        $session->remove('_auth_user_id');
        $session->remove('_auth_user_roles');
        $session->regenerate();
    }

    /**
     * Check if a user is logged in
     */
    public function isLoggedIn(SessionInterface $session): bool
    {
        return $session->has('_auth_user_id');
    }

    /**
     * Get current user
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

    file_put_contents($path, $content);
}

function generateLoginFormType(string $path): void
{
    $content = <<<'PHP'
<?php

namespace App\Form;

use Ogan\Form\AbstractType;
use Ogan\Form\FormBuilder;
use Ogan\Form\Types\EmailType;
use Ogan\Form\Types\PasswordType;
use Ogan\Form\Types\CheckboxType;
use Ogan\Form\Types\SubmitType;
use Ogan\Form\Constraint\Required;
use Ogan\Form\Constraint\Email;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Required('Email is required.'),
                    new Email('Please enter a valid email address.'),
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent',
                    'placeholder' => 'your@email.com',
                    'autofocus' => true
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'constraints' => [
                    new Required('Password is required.'),
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent',
                    'placeholder' => 'Your password'
                ]
            ])
            ->add('remember_me', CheckboxType::class, [
                'label' => 'Se souvenir de moi',
                'constraints' => [],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sign In',
                'attr' => ['class' => 'w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors']
            ]);
    }
}
PHP;

    file_put_contents($path, $content);
}

function generateRegisterFormType(string $path): void
{
    $content = <<<'PHP'
<?php

namespace App\Form;

use App\Model\User;
use Ogan\Form\AbstractType;
use Ogan\Form\FormBuilder;
use Ogan\Form\Types\TextType;
use Ogan\Form\Types\EmailType;
use Ogan\Form\Types\PasswordType;
use Ogan\Form\Types\SubmitType;
use Ogan\Form\Constraint\Required;
use Ogan\Form\Constraint\MinLength;
use Ogan\Form\Constraint\MaxLength;
use Ogan\Form\Constraint\Email;
use Ogan\Form\Constraint\EqualTo;
use Ogan\Form\Constraint\UniqueEntity;

class RegisterFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'constraints' => [
                    new Required('Name is required.'),
                    new MinLength(2, 'Name must be at least 2 characters.'),
                    new MaxLength(100, 'Name must not exceed 100 characters.'),
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent',
                    'placeholder' => 'Your full name',
                    'autofocus' => true
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Required('Email is required.'),
                    new Email('Please enter a valid email address.'),
                    new UniqueEntity(User::class, 'email', 'This email is already used.'),
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent',
                    'placeholder' => 'your@email.com'
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'constraints' => [
                    new Required('Password is required.'),
                    new MinLength(8, 'Password must be at least 8 characters.'),
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent',
                    'placeholder' => 'Minimum 8 characters'
                ]
            ])
            ->add('password_confirm', PasswordType::class, [
                'label' => 'Confirm Password',
                'constraints' => [
                    new Required('Please confirm your password.'),
                    new EqualTo('password', 'Passwords do not match.'),
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent',
                    'placeholder' => 'Retype your password'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Create Account',
                'attr' => ['class' => 'w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors']
            ]);
    }
}
PHP;

    file_put_contents($path, $content);
}

function generateForgotPasswordFormType(string $path): void
{
    $content = <<<'PHP'
<?php

namespace App\Form;

use Ogan\Form\AbstractType;
use Ogan\Form\FormBuilder;
use Ogan\Form\Types\EmailType;
use Ogan\Form\Types\SubmitType;

class ForgotPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Votre adresse email',
                'required' => true,
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent',
                    'placeholder' => 'votre@email.com',
                    'autofocus' => true
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer le lien de réinitialisation',
                'attr' => ['class' => 'w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors']
            ]);
    }
}
PHP;

    file_put_contents($path, $content);
}

function generateResetPasswordFormType(string $path): void
{
    $content = <<<'PHP'
<?php

namespace App\Form;

use Ogan\Form\AbstractType;
use Ogan\Form\FormBuilder;
use Ogan\Form\Types\PasswordType;
use Ogan\Form\Types\SubmitType;

class ResetPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, [
                'label' => 'Nouveau mot de passe',
                'required' => true,
                'min' => 8,
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent',
                    'placeholder' => 'Minimum 8 caractères',
                    'autofocus' => true
                ]
            ])
            ->add('password_confirm', PasswordType::class, [
                'label' => 'Confirmer le mot de passe',
                'required' => true,
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent',
                    'placeholder' => 'Retapez votre mot de passe'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Réinitialiser mon mot de passe',
                'attr' => ['class' => 'w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors']
            ]);
    }
}
PHP;

    file_put_contents($path, $content);
}

function generateLoginTemplate(string $path): void
{
    $content = <<<'OGAN'
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Connexion</h1>
            <p class="text-gray-600 mt-2">Connectez-vous à votre compte</p>
        </div>

        {% form.render() %}

        <div class="mt-6 text-center text-sm">
            <a href="/forgot-password" class="text-indigo-600 hover:text-indigo-500">
                Mot de passe oublié ?
            </a>
        </div>

        <div class="mt-4 text-center text-sm text-gray-600">
            Pas encore de compte ?
            <a href="/register" class="text-indigo-600 hover:text-indigo-500 font-semibold">
                Créer un compte
            </a>
        </div>
    </div>
</div>
{{ end }}
OGAN;

    file_put_contents($path, $content);
}

function generateRegisterTemplate(string $path): void
{
    $content = <<<'OGAN'
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Inscription</h1>
            <p class="text-gray-600 mt-2">Créez votre compte gratuitement</p>
        </div>

        {% form.render() %}

        <div class="mt-6 text-center text-sm text-gray-600">
            Déjà un compte ?
            <a href="/login" class="text-indigo-600 hover:text-indigo-500 font-semibold">
                Se connecter
            </a>
        </div>
    </div>
</div>
{{ end }}
OGAN;

    file_put_contents($path, $content);
}

function generateForgotPasswordTemplate(string $path): void
{
    $content = <<<'OGAN'
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Mot de passe oublié</h1>
            <p class="text-gray-600 mt-2">Entrez votre email pour recevoir un lien de réinitialisation</p>
        </div>

        {% if emailSent %}
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <p class="font-semibold">Email envoyé !</p>
            <p class="text-sm">Si un compte existe avec cette adresse, vous recevrez un email avec les instructions.</p>
        </div>
        {% endif %}

        {% form.render() %}

        <div class="mt-6 text-center text-sm">
            <a href="/login" class="text-indigo-600 hover:text-indigo-500">
                ← Retour à la connexion
            </a>
        </div>
    </div>
</div>
{{ end }}
OGAN;

    file_put_contents($path, $content);
}

function generateResetPasswordTemplate(string $path): void
{
    $content = <<<'OGAN'
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Nouveau mot de passe</h1>
            <p class="text-gray-600 mt-2">Choisissez un nouveau mot de passe sécurisé</p>
        </div>

        {% form.render() %}

        <div class="mt-6 text-center text-sm">
            <a href="/login" class="text-indigo-600 hover:text-indigo-500">
                ← Retour à la connexion
            </a>
        </div>
    </div>
</div>
{{ end }}
OGAN;

    file_put_contents($path, $content);
}

function generateUsersMigration(string $path): void
{
    $content = <<<'PHP'
<?php

use Ogan\Database\Migration\AbstractMigration;

class CreateUsersTable extends AbstractMigration
{
    protected string $table = 'users';

    public function up(): void
    {
        $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $sql = match ($driver) {
            'mysql' => "
                CREATE TABLE users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    roles JSON DEFAULT NULL,
                    email_verified_at DATETIME DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_email (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'pgsql' => "
                CREATE TABLE users (
                    id SERIAL PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    roles JSONB DEFAULT '[]',
                    email_verified_at TIMESTAMP DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX idx_users_email ON users(email);
            ",
            'sqlite' => "
                CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    roles TEXT DEFAULT '[]',
                    email_verified_at DATETIME DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX idx_users_email ON users(email);
            ",
            default => throw new \RuntimeException("Driver non supporté: {$driver}")
        };

        $this->pdo->exec($sql);
    }

    public function down(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS users");
    }
}
PHP;

    file_put_contents($path, $content);
}

function generateRememberTokensMigration(string $path): void
{
    $content = <<<'PHP'
<?php

use Ogan\Database\Migration\AbstractMigration;

class CreateRememberTokensTable extends AbstractMigration
{
    protected string $table = 'remember_tokens';

    public function up(): void
    {
        $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $sql = match ($driver) {
            'mysql', 'mariadb' => "
                CREATE TABLE remember_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_token (token),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'pgsql', 'postgresql' => "
                CREATE TABLE remember_tokens (
                    id SERIAL PRIMARY KEY,
                    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                    token VARCHAR(255) NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX idx_remember_tokens_user_id ON remember_tokens(user_id);
                CREATE INDEX idx_remember_tokens_token ON remember_tokens(token);
            ",
            'sqlite' => "
                CREATE TABLE remember_tokens (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );
                CREATE INDEX idx_remember_tokens_user_id ON remember_tokens(user_id);
                CREATE INDEX idx_remember_tokens_token ON remember_tokens(token);
            ",
            default => throw new \RuntimeException("Driver non supporté: {$driver}")
        };

        $this->pdo->exec($sql);
    }

    public function down(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS remember_tokens");
    }
}
PHP;

    file_put_contents($path, $content);
}

function generateUserRepository(string $path): void
{
    $content = <<<'PHP'
<?php

namespace App\Repository;

use App\Model\User;
use Ogan\Database\AbstractRepository;

class UserRepository extends AbstractRepository
{
    protected string $entityClass = User::class;
    protected string $table = 'users';

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }
}
PHP;
    file_put_contents($path, $content);
}

function generateDashboardController(string $path): void
{
    $content = <<<'PHP'
<?php

namespace App\Controller;

use Ogan\Controller\AbstractController;
use Ogan\Http\Request;
use Ogan\Http\Response;
use Ogan\Router\Attributes\Route;
use Ogan\Security\PasswordHasher;
use App\Security\UserAuthenticator;
use App\Form\ProfileFormType;

class DashboardController extends AbstractController
{
    private ?UserAuthenticator $auth = null;

    private function getAuth(): UserAuthenticator
    {
        if ($this->auth === null) {
            $this->auth = new UserAuthenticator();
        }
        return $this->auth;
    }

    #[Route(path: '/dashboard', methods: ['GET'], name: 'dashboard_index')]
    public function index(): Response
    {
        if (!$this->getAuth()->isLoggedIn($this->session)) {
            return $this->redirect('/login');
        }

        $user = $this->getAuth()->getUser($this->session);

        return $this->render('dashboard/index.ogan', [
            'user' => $user
        ]);
    }

    #[Route(path: '/profile', methods: ['GET'], name: 'user_profile')]
    public function profile(): Response
    {
        if (!$this->getAuth()->isLoggedIn($this->session)) {
            return $this->redirect('/login');
        }

        $user = $this->getAuth()->getUser($this->session);

        return $this->render('user/profile.ogan', [
            'title' => 'Mon Profil',
            'user' => $user
        ]);
    }

    #[Route(path: '/profile/edit', methods: ['GET', 'POST'], name: 'user_profile_edit')]
    public function editProfile(): Response
    {
        if (!$this->getAuth()->isLoggedIn($this->session)) {
            return $this->redirect('/login');
        }

        $user = $this->getAuth()->getUser($this->session);

        $form = $this->formFactory->create(ProfileFormType::class, [
            'action' => '/profile/edit',
            'method' => 'POST'
        ]);

        // Pré-remplir avec les données actuelles
        $form->setData([
            'name' => $user->getName(),
            'email' => $user->getEmail()
        ]);

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                // Mettre à jour les informations de base
                $user->setName($data['name']);
                $user->setEmail($data['email']);

                // Si un nouveau mot de passe est fourni
                if (!empty($data['new_password'])) {
                    $hasher = new PasswordHasher();
                    // Vérifier le mot de passe actuel
                    if (empty($data['current_password']) || !$hasher->verify($data['current_password'], $user->getPassword())) {
                        $form->addError('current_password', 'Le mot de passe actuel est incorrect.');
                        return $this->render('user/edit.ogan', [
                            'title' => 'Modifier mon profil',
                            'user' => $user,
                            'form' => $form->createView()
                        ]);
                    }
                    $user->setPassword($hasher->hash($data['new_password']));
                }

                $user->save();

                $this->session->setFlash('success', 'Votre profil a été mis à jour avec succès.');
                return $this->redirect('/profile');
            }
        }

        return $this->render('user/edit.ogan', [
            'title' => 'Modifier mon profil',
            'user' => $user,
            'form' => $form->createView()
        ]);
    }
}
PHP;
    file_put_contents($path, $content);
}

function generateDashboardComponents(string $projectRoot): void
{
    // Create components directory if not exists
    $componentsDir = $projectRoot . '/templates/components/dashboard';
    if (!is_dir($componentsDir)) {
        mkdir($componentsDir, 0777, true);
    }

    // Sidebar Component
    $sidebarContent = <<<'HTML'
<div class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 shadow-sm transition-transform -translate-x-full md:translate-x-0" id="sidebar">
    <div class="flex items-center justify-center h-16 border-b border-gray-200 dark:border-gray-700 px-6">
        <span class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">DELNYX</span>
    </div>
    
    <nav class="p-4 space-y-1">
        <a href={{ route('dashboard_index') }} class="flex items-center px-4 py-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 group transition-colors">
            <svg class="w-5 h-5 mr-3 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Dashboard
        </a>
        
        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Gestion</p>
        </div>
        
        <a href="#" class="flex items-center px-4 py-3 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 group transition-colors">
            <svg class="w-5 h-5 mr-3 text-gray-400 dark:text-gray-500 group-hover:text-blue-600 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            Utilisateurs
        </a>

        <a href="#" class="flex items-center px-4 py-3 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 group transition-colors">
            <svg class="w-5 h-5 mr-3 text-gray-400 dark:text-gray-500 group-hover:text-blue-600 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Documents
        </a>
    </nav>
</div>
HTML;
    file_put_contents($componentsDir . '/sidebar.ogan', $sidebarContent);

    // Navbar Component
    $navbarContent = <<<'HTML'
<header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8">
    <!-- Mobile menu button -->
    <button type="button" class="md:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none" onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
    </button>

    <div class="flex-1 flex justify-end items-center space-x-4">
        
        <!-- Dark Mode Toggle -->
        <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
            <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
            <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
        </button>

        <!-- User Dropdown -->
        <div class="relative ml-3 group">
            <button type="button" class="peer flex items-center max-w-xs text-sm bg-white dark:bg-gray-800 rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                <span class="sr-only">Open user menu</span>
                <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold">
                    {{ user.name|first|upper }}
                </div>
                <span class="ml-3 hidden md:block text-sm font-medium text-gray-700 dark:text-gray-200">{{ user.name }}</span>
                <svg class="ml-2 h-5 w-5 text-gray-400 group-hover:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
            </button>
            
            <!-- Dropdown menu - appears on focus or hover -->
            <div class="hidden peer-focus:block hover:block focus-within:block absolute right-0 w-56 mt-2 origin-top-right bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Connecté en tant que</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</p>
                </div>
                <div class="py-1">
                    <a href="{{ route('user_profile') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Mon Profil
                    </a>
                </div>
                <div class="border-t border-gray-100 dark:border-gray-700 py-1">
                    <a href="{{ route('security_logout') }}" class="flex items-center px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
HTML;
    file_put_contents($componentsDir . '/navbar.ogan', $navbarContent);
}

function generateDashboardLayout(string $path): void
{
    $content = <<<'HTML'
{{ title = title ?? 'Dashboard' }}
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ title }} - Delnyx</title>
    <link rel="stylesheet" href="{{ asset('/assets/css/app.css') }}">
    <!-- Theme Script (in head to avoid FOUC) -->
    <script src="{{ asset('/assets/js/theme.js') }}"></script>
</head>
<body class="h-full dark:bg-gray-900 transition-colors duration-200">

    <div class="min-h-full">
        
        <!-- Sidebar -->
        {{ component('dashboard/sidebar') }}

        <!-- Main Content -->
        <div class="md:pl-64 flex flex-col min-h-screen transition-all duration-300">
            
            <!-- Navbar -->
            {{ component('dashboard/navbar', ['user' => user]) }}

            <!-- Main Content Area -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 bg-gray-50 dark:bg-gray-900">
                {{ component('flashes') }}

                {{ section('content') }}
            </main>
        </div>
    </div>
</body>
</html>
HTML;
    file_put_contents($path, $content);
}

function generateDashboardIndex(string $path): void
{
    $content = <<<'HTML'
{{ extend('dashboard/layout.ogan') }}

{{ start('content') }}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Bienvenue, {{ user.name }}</h1>
        <p class="text-gray-600 dark:text-gray-300">
            Ceci est votre tableau de bord. Commencez à gérer votre application dès maintenant.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Stat Card 1 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Utilisateurs</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">1,234</p>
                </div>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Revenus</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">€45,678</p>
                </div>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Documents</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">89</p>
                </div>
            </div>
        </div>
    </div>
{{ end }}
HTML;
    file_put_contents($path, $content);
}

function generateThemeJs(string $path): void
{
    $content = <<<'JS'
/**
 * Theme Toggle (Dark Mode)
 * Handles dark mode initialization and toggling with localStorage persistence
 */
(function() {
    'use strict';

    // Initialize theme on page load (before DOM ready to avoid FOUC)
    function initTheme() {
        if (localStorage.getItem('color-theme') === 'dark' || 
            (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    // Update icons based on current theme
    function updateIcons() {
        var darkIcon = document.getElementById('theme-toggle-dark-icon');
        var lightIcon = document.getElementById('theme-toggle-light-icon');
        
        if (!darkIcon || !lightIcon) return;
        
        if (document.documentElement.classList.contains('dark')) {
            darkIcon.classList.add('hidden');
            lightIcon.classList.remove('hidden');
        } else {
            darkIcon.classList.remove('hidden');
            lightIcon.classList.add('hidden');
        }
    }

    // Toggle theme
    function toggleTheme() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
        }
        updateIcons();
    }

    // Initialize immediately
    initTheme();

    // Setup toggle button after DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            updateIcons();
            var toggleBtn = document.getElementById('theme-toggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', toggleTheme);
            }
        });
    } else {
        updateIcons();
        var toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleTheme);
        }
    }
})();
JS;
    file_put_contents($path, $content);
}

function generateFlashesJs(string $path): void
{
    $content = <<<'JS'
/**
 * Flash Messages Auto-Dismiss
 * Automatically dismisses flash messages after 5 seconds
 */
(function() {
    'use strict';

    function initFlashMessages() {
        var flashes = document.querySelectorAll('.flash-message');
        flashes.forEach(function(flash) {
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                flash.style.transition = 'opacity 0.3s ease-out';
                flash.style.opacity = '0';
                setTimeout(function() {
                    flash.remove();
                }, 300);
            }, 5000);
        });
    }

    // Initialize on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFlashMessages);
    } else {
        initFlashMessages();
    }
})();
JS;
    file_put_contents($path, $content);
}

function generateFlashesComponent(string $path): void
{
    $content = <<<'OGAN'
{% for type, messages in getAllFlashes() %}
	{% for message in messages %}
		<div class="flash-message mb-4 px-4 py-3 rounded-lg relative flex items-center justify-between {% if type == 'success' %}bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300{%
		 elseif type == 'error' %}bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300{%
		 elseif type == 'warning' %}bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300{%
		 elseif type == 'info' %}bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300{%
		 else %}bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300{% endif %}">
			<span>{{ message }}</span>
			<button type="button" onclick="this.parentElement.remove()" class="ml-4 inline-flex items-center justify-center p-1 rounded-full hover:bg-black/10 dark:hover:bg-white/10 transition-colors">
				<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
					<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
				</svg>
			</button>
		</div>
	{% endfor %}
{% endfor %}

<script src="{{ asset('/assets/js/flashes.js') }}"></script>
OGAN;
    file_put_contents($path, $content);
}

function generateProfileTemplate(string $path): void
{
    $content = <<<'OGAN'
{{ extend('dashboard/layout.ogan') }}

{{ start('content') }}
<div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
	<div class="px-4 py-5 sm:px-6 flex justify-between items-center">
		<div>
			<h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
				Informations personnelles
			</h3>
			<p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
				Détails de votre compte utilisateur.
			</p>
		</div>
		<a href="{{ route('user_profile_edit') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
			Modifier
		</a>
	</div>
	<div class="border-t border-gray-200 dark:border-gray-700">
		<dl>
			<div class="bg-gray-50 dark:bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
				<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
					Nom complet
				</dt>
				<dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
					{{ user.name }}
				</dd>
			</div>
			<div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
				<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
					Adresse email
				</dt>
				<dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
					{{ user.email }}
				</dd>
			</div>
			<div class="bg-gray-50 dark:bg-gray-900 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
				<dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
					Compte créé le
				</dt>
				<dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
					{{ user.getCreatedAt|date("d/m/Y H:i") }}
				</dd>
			</div>
		</dl>
	</div>
</div>
{{ end }}
OGAN;
    file_put_contents($path, $content);
}

function generateProfileEditTemplate(string $path): void
{
    $content = <<<'OGAN'
{{ extend('dashboard/layout.ogan') }}

{{ start('content') }}
<div class="max-w-2xl mx-auto">
	<div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
		<div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
			<div class="flex items-center justify-between">
				<div>
					<h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
						Modifier mon profil
					</h3>
					<p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
						Mettez à jour vos informations personnelles.
					</p>
				</div>
				<a href="{{ route('user_profile') }}" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
					← Retour
				</a>
			</div>
		</div>
		<div class="px-4 py-5 sm:p-6">
			{% form.render() %}
		</div>
	</div>

	<div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
		<div class="flex">
			<div class="flex-shrink-0">
				<svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
					<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
				</svg>
			</div>
			<div class="ml-3">
				<h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
					Changement de mot de passe
				</h3>
				<p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
					Pour changer votre mot de passe, remplissez les trois champs de mot de passe. 
					Laissez-les vides pour conserver votre mot de passe actuel.
				</p>
			</div>
		</div>
	</div>
</div>
{{ end }}
OGAN;
    file_put_contents($path, $content);
}

function generateProfileFormType(string $path): void
{
    $content = <<<'PHP'
<?php

namespace App\Form;

use Ogan\Form\AbstractType;
use Ogan\Form\FormBuilder;
use Ogan\Form\Types\TextType;
use Ogan\Form\Types\EmailType;
use Ogan\Form\Types\PasswordType;
use Ogan\Form\Types\SubmitType;
use Ogan\Form\Constraint\Required;
use Ogan\Form\Constraint\Email;
use Ogan\Form\Constraint\MinLength;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom complet',
                'constraints' => [
                    new Required('Le nom est obligatoire'),
                    new MinLength(2, 'Le nom doit contenir au moins 2 caractères')
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                    'placeholder' => 'Votre nom'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'constraints' => [
                    new Required('L\'email est obligatoire'),
                    new Email('L\'email n\'est pas valide')
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                    'placeholder' => 'votre@email.com'
                ]
            ])
            ->add('current_password', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'required' => false,
                'constraints' => [],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                    'placeholder' => 'Requis pour changer le mot de passe'
                ]
            ])
            ->add('new_password', PasswordType::class, [
                'label' => 'Nouveau mot de passe',
                'required' => false,
                'constraints' => [],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                    'placeholder' => 'Laisser vide pour ne pas changer'
                ]
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirmer le nouveau mot de passe',
                'required' => false,
                'constraints' => [],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                    'placeholder' => 'Confirmer le nouveau mot de passe'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer les modifications',
                'attr' => ['class' => 'w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors']
            ]);
    }
}
PHP;
    file_put_contents($path, $content);
}

