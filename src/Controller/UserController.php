<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 👤 USER CONTROLLER - Gestion des Utilisateurs
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Exemple complet d'utilisation du framework :
 * - Routing avec attributs
 * - FormTypes
 * - ORM (Model)
 * - Validation
 * - Sessions
 * - Sécurité (CSRF)
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\Controller;

use Ogan\Controller\AbstractController;
use Ogan\Router\Attributes\Route;
use App\Model\User;
use App\Form\UserRegistrationFormType;
use App\Form\UserLoginFormType;
use App\Form\UserEditFormType;

class UserController extends AbstractController
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * 📋 LISTE DES UTILISATEURS
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/users', methods: ['GET'], name: 'user_list')]
    public function list()
    {
        $users = User::all();

        return $this->render('user/list.ogan', [
            'title' => 'Liste des utilisateurs',
            'users' => $users
        ]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * 👁️ VOIR UN UTILISATEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/users/{id}', methods: ['GET'], name: 'user_show')]
    public function show(int $id)
    {
        $user = User::find($id);

        if (!$user) {
            $this->session->setFlash('error', 'Utilisateur non trouvé');
            return $this->redirect('/users');
        }

        return $this->render('user/show.ogan', [
            'title' => 'Détails de l\'utilisateur',
            'user' => $user
        ]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ✏️ ÉDITER UN UTILISATEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/users/{id}/edit', methods: ['GET', 'POST'], name: 'user_edit')]
    public function edit(int $id)
    {
        $user = User::find($id);

        if (!$user) {
            $this->session->setFlash('error', 'Utilisateur non trouvé');
            return $this->redirect('/users');
        }

        $form = $this->formFactory->create(UserEditFormType::class, [
            'action' => "/users/{$id}/edit",
            'method' => 'POST'
        ]);

        // Pré-remplir le formulaire avec les données de l'utilisateur
        $form->setData([
            'name' => $user->getName() ?? '',
            'email' => $user->getEmail() ?? ''
        ]);

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);

            if ($form->isValid()) {
                $data = $form->getData();

                // Vérifier si l'email existe déjà (sauf pour cet utilisateur)
                $existingResult = User::where('email', '=', $data['email'])
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingResult !== null) {
                    $form->addError('email', 'Cet email est déjà utilisé');
                } else {
                    // Mettre à jour l'utilisateur
                    $user->setName($data['name']);
                    $user->setEmail($data['email']);
                    $user->save();

                    $this->session->setFlash('success', 'Utilisateur mis à jour avec succès');
                    return $this->redirect("/users/{$id}");
                }
            }
        }

        return $this->render('user/edit.ogan', [
            'title' => 'Modifier l\'utilisateur',
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * 🗑️ SUPPRIMER UN UTILISATEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/users/{id}/delete', methods: ['POST'], name: 'user_delete')]
    public function delete(int $id)
    {
        $user = User::find($id);

        if (!$user) {
            $this->session->setFlash('error', 'Utilisateur non trouvé');
            return $this->redirect('/users');
        }

        $user->delete();

        $this->session->setFlash('success', 'Utilisateur supprimé avec succès');
        return $this->redirect('/users');
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * 📝 INSCRIPTION
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/register', methods: ['GET', 'POST'], name: 'user_register')]
    public function register()
    {
        $form = $this->formFactory->create(UserRegistrationFormType::class, [
            'action' => '/register',
            'method' => 'POST'
        ]);

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);

            if ($form->isValid()) {
                $data = $form->getData();

                // Vérifier que les mots de passe correspondent
                if ($data['password'] !== $data['password_confirm']) {
                    $form->addError('password_confirm', 'Les mots de passe ne correspondent pas');
                } else {
                    // Vérifier si l'email existe déjà
                    $existingUser = User::findByEmail($data['email']);

                    if ($existingUser) {
                        $form->addError('email', 'Cet email est déjà utilisé');
                    } else {
                        // Créer l'utilisateur
                        $passwordHasher = $this->container->get(\Ogan\Security\PasswordHasher::class);
                        
                        $user = new User();
                        $user->setName($data['name']);
                        $user->setEmail($data['email']);
                        $user->setPassword($passwordHasher->hash($data['password']));
                        // created_at et updated_at sont gérés automatiquement par Model::save()
                        $user->save();

                        $this->session->setFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
                        return $this->redirect('/login');
                    }
                }
            }
        }

        return $this->render('user/register.ogan', [
            'title' => 'Inscription',
            'form' => $form->createView()
        ]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * 🔐 CONNEXION
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/login', methods: ['GET', 'POST'], name: 'user_login')]
    public function login()
    {
        // Si déjà connecté, rediriger
        if ($this->session->get('user_id')) {
            return $this->redirect('/users');
        }

        $form = $this->formFactory->create(UserLoginFormType::class, [
            'action' => '/login',
            'method' => 'POST'
        ]);

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);

            if ($form->isValid()) {
                $data = $form->getData();

                // Chercher l'utilisateur
                $user = User::findByEmail($data['email']);

                if ($user) {
                    $passwordHasher = $this->container->get(\Ogan\Security\PasswordHasher::class);
                    if ($passwordHasher->verify($data['password'], $user->getPassword() ?? '')) {
                        // Connexion réussie
                        $this->session->set('user_id', $user->getId());
                        $this->session->set('user_name', $user->getName());
                        $this->session->setFlash('success', 'Connexion réussie !');

                        return $this->redirect('/users');
                    } else {
                        $form->addError('email', 'Email ou mot de passe incorrect');
                    }
                } else {
                    $form->addError('email', 'Email ou mot de passe incorrect');
                }
            }
        }

        return $this->render('user/login.ogan', [
            'title' => 'Connexion',
            'form' => $form->createView()
        ]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * 🚪 DÉCONNEXION
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/logout', methods: ['GET'], name: 'user_logout')]
    public function logout()
    {
        $this->session->destroy();
        $this->session->setFlash('success', 'Déconnexion réussie');

        return $this->redirect('/login');
    }
}

