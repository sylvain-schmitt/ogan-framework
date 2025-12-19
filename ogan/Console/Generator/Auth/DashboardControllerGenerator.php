<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📊 DASHBOARD CONTROLLER GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class DashboardControllerGenerator extends AbstractGenerator
{
    public function generate(string $projectRoot, bool $force = false): array
    {
        $generated = [];
        $skipped = [];

        $path = $projectRoot . '/src/Controller/DashboardController.php';
        $this->ensureDirectory(dirname($path));

        if (!$this->fileExists($path) || $force) {
            $this->writeFile($path, $this->getTemplate());
            $generated[] = 'src/Controller/DashboardController.php';
        } else {
            $skipped[] = 'src/Controller/DashboardController.php (existe déjà)';
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getTemplate(): string
    {
        return <<<'PHP'
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

                $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
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
    }
}
