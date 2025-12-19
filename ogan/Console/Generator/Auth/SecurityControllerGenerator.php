<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 SECURITY CONTROLLER GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class SecurityControllerGenerator extends AbstractGenerator
{
    public function generate(string $projectRoot, bool $force = false): array
    {
        $generated = [];
        $skipped = [];

        $path = $projectRoot . '/src/Controller/SecurityController.php';
        $this->ensureDirectory(dirname($path));

        if (!$this->fileExists($path) || $force) {
            $this->writeFile($path, $this->getTemplate());
            $generated[] = 'src/Controller/SecurityController.php';
        } else {
            $skipped[] = 'src/Controller/SecurityController.php (existe déjà)';
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getTemplate(): string
    {
        return <<<'PHP'
<?php

namespace App\Controller;

use App\Form\LoginFormType;
use App\Form\RegisterFormType;
use App\Form\ForgotPasswordFormType;
use App\Form\ResetPasswordFormType;
use App\Model\User;
use App\Security\UserAuthenticator;
use App\Security\EmailVerificationService;
use App\Security\PasswordResetService;
use Ogan\Config\Config;
use Ogan\Router\Attributes\Route;
use Ogan\Controller\AbstractController;
use Ogan\Security\RememberMeService;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 SECURITY CONTROLLER
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Routes d'authentification: login, logout, register, 
 * forgot password, reset password, verify email.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
class SecurityController extends AbstractController
{
    private UserAuthenticator $authenticator;
    private EmailVerificationService $verificationService;
    private PasswordResetService $resetService;
    private RememberMeService $rememberMe;

    public function __construct()
    {
        $this->authenticator = new UserAuthenticator();
        $this->verificationService = new EmailVerificationService();
        $this->resetService = new PasswordResetService();
        $this->rememberMe = new RememberMeService();
    }

    // ═══════════════════════════════════════════════════════════════════
    // 🔐 LOGIN
    // ═══════════════════════════════════════════════════════════════════
    
    #[Route(path: '/login', methods: ['GET', 'POST'], name: 'login')]
    public function login()
    {
        if ($this->authenticator->isLoggedIn($this->session)) {
            return $this->redirect(Config::get('auth.login_redirect', '/dashboard'));
        }

        $form = $this->formFactory->create(LoginFormType::class, [
            'action' => '/login',
            'method' => 'POST'
        ]);

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $user = $this->authenticator->attempt($data['email'], $data['password']);

                if ($user) {
                    // Vérifier si l'email doit être validé
                    if (Config::get('auth.send_verification_email', false) && !$user->isVerified()) {
                        $form->addError('email', 'Veuillez vérifier votre adresse email avant de vous connecter.');
                    } else {
                        $this->authenticator->login($user, $this->session);
                        
                        // Handle remember me
                        if (!empty($data['remember_me'])) {
                            $this->rememberMe->createToken($user->id);
                        }
                        
                        return $this->redirect(Config::get('auth.login_redirect', '/dashboard'));
                    }
                } else {
                    $form->addError('email', 'Email ou mot de passe incorrect.');
                }
            }
        }

        return $this->render('security/login.ogan', [
            'title' => 'Connexion',
            'form' => $form->createView(),
            'show_forgot_password' => Config::get('auth.send_password_reset_email', false)
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // 🚪 LOGOUT
    // ═══════════════════════════════════════════════════════════════════
    
    #[Route(path: '/logout', methods: ['GET'], name: 'logout')]
    public function logout()
    {
        $user = $this->authenticator->getUser($this->session);
        if ($user) {
            $this->rememberMe->deleteAllUserTokens($user->id);
        }
        $this->rememberMe->clearCookie();
        
        $this->authenticator->logout($this->session);
        return $this->redirect(Config::get('auth.logout_redirect', '/login'));
    }

    // ═══════════════════════════════════════════════════════════════════
    // 📝 REGISTER
    // ═══════════════════════════════════════════════════════════════════
    
    #[Route(path: '/register', methods: ['GET', 'POST'], name: 'register')]
    public function register()
    {
        if ($this->authenticator->isLoggedIn($this->session)) {
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
                $requireVerification = Config::get('auth.send_verification_email', false);
                
                $user = $this->authenticator->register($data, !$requireVerification);
                
                if ($requireVerification) {
                    $this->verificationService->sendVerification($user);
                    $this->addFlash('success', 'Compte créé ! Vérifiez votre email pour activer votre compte.');
                    return $this->redirect('/login');
                }
                
                $this->authenticator->login($user, $this->session);
                $this->addFlash('success', 'Compte créé avec succès !');
                return $this->redirect(Config::get('auth.login_redirect', '/dashboard'));
            }
        }

        return $this->render('security/register.ogan', [
            'title' => 'Inscription',
            'form' => $form->createView()
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // ✉️ VERIFY EMAIL
    // ═══════════════════════════════════════════════════════════════════
    
    #[Route(path: '/verify-email/{token}', methods: ['GET'], name: 'verify_email')]
    public function verifyEmail(string $token)
    {
        $user = $this->verificationService->verify($token);
        
        if (!$user) {
            $this->addFlash('error', 'Lien de vérification invalide ou expiré.');
            return $this->redirect('/login');
        }
        
        $this->addFlash('success', 'Email vérifié avec succès ! Vous pouvez maintenant vous connecter.');
        return $this->redirect('/login');
    }

    // ═══════════════════════════════════════════════════════════════════
    // 🔑 FORGOT PASSWORD
    // ═══════════════════════════════════════════════════════════════════
    
    #[Route(path: '/forgot-password', methods: ['GET', 'POST'], name: 'forgot_password')]
    public function forgotPassword()
    {
        // Vérifier si la fonctionnalité de reset password est activée
        $resetEnabled = Config::get('auth.send_password_reset_email', false);
        if (!$resetEnabled) {
            $this->addFlash('error', 'La réinitialisation du mot de passe n\'est pas disponible.');
            return $this->redirect('/login');
        }

        if ($this->authenticator->isLoggedIn($this->session)) {
            return $this->redirect('/');
        }

        $sendEmail = Config::get('auth.send_password_reset_email', false);

        $form = $this->formFactory->create(ForgotPasswordFormType::class, [
            'action' => '/forgot-password',
            'method' => 'POST',
            'send_email' => $sendEmail
        ]);

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $user = User::findByEmail($data['email']);
                
                if ($sendEmail) {
                    if ($user) {
                        $this->resetService->sendResetEmail($user);
                    }
                    // Always show success to prevent email enumeration
                    $this->addFlash('success', 'Si cet email existe, un lien de réinitialisation a été envoyé.');
                    return $this->redirect('/login');
                } else {
                    // Direct mode
                    if ($this->resetService->resetPasswordDirect($data['email'], $data['new_password'])) {
                        $this->addFlash('success', 'Mot de passe modifié avec succès !');
                        return $this->redirect('/login');
                    }
                    $form->addError('email', 'Aucun compte associé à cet email.');
                }
            }
        }

        return $this->render('security/forgot_password.ogan', [
            'title' => 'Mot de passe oublié',
            'form' => $form->createView(),
            'send_email' => $sendEmail,
            'emailSent' => false
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // 🔑 RESET PASSWORD (with token from email)
    // ═══════════════════════════════════════════════════════════════════
    
    #[Route(path: '/reset-password/{token}', methods: ['GET', 'POST'], name: 'reset_password')]
    public function resetPassword(string $token)
    {
        $user = $this->resetService->validateToken($token);
        
        if (!$user) {
            $this->addFlash('error', 'Lien de réinitialisation invalide ou expiré.');
            return $this->redirect('/forgot-password');
        }

        $form = $this->formFactory->create(ResetPasswordFormType::class, [
            'action' => '/reset-password/' . $token,
            'method' => 'POST'
        ]);

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                
                if ($this->resetService->resetPassword($user, $data['password'])) {
                    $this->addFlash('success', 'Mot de passe modifié avec succès !');
                    return $this->redirect('/login');
                }
            }
        }

        return $this->render('security/reset_password.ogan', [
            'title' => 'Réinitialiser le mot de passe',
            'form' => $form->createView(),
            'token' => $token
        ]);
    }
}
PHP;
    }
}
