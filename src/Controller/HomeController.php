<?php

namespace App\Controller;

use Ogan\Router\Attributes\Route;
use Ogan\Controller\AbstractController;


class HomeController extends AbstractController
{
    #[Route(path: '/', methods: ['GET'], name: 'home_index')]
    public function index()
    {
        // Compter les utilisateurs (si la table existe)
        $userCount = 0;
        try {
            if (class_exists(\App\Model\User::class)) {
                $userCount = \App\Model\User::count();
            }
        } catch (\Exception $e) {
            // Table n'existe pas encore
        }

        return $this->render('home/index.ogan', [
            'title' => 'Accueil - Ogan Framework',
            'name' => 'Ogan Framework',
            'user_count' => $userCount,
            'features' => [
                [
                    'title' => 'Routing Puissant',
                    'description' => 'Support des attributs PHP 8, paramètres typés, et contraintes Regex.',
                    'image' => 'https://images.unsplash.com/photo-1555099962-4199c345e5dd?auto=format&fit=crop&w=500&q=80',
                    'link' => '/users'
                ],
                [
                    'title' => 'Template Engine',
                    'description' => 'Système de layout, blocs, et composants réutilisables (comme ces cartes !).',
                    'image' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=500&q=80',
                    'link' => '/users'
                ],
                [
                    'title' => 'Injection de Dépendances',
                    'description' => 'Conteneur de services léger et performant avec autowiring.',
                    'image' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=500&q=80',
                    'link' => '/users'
                ]
            ]
        ]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * 📝 EXEMPLES DE ROUTES AVEC DÉTECTION AUTOMATIQUE
     * ═══════════════════════════════════════════════════════════════════
     */

    /**
     * Route avec {id} → détection automatique numérique
     * Exemple: /article/123
     */
    #[Route(path: '/article/{id}', methods: ['GET'], name: 'article_show')]
    public function articleShow(int $id)
    {
        return $this->render('home/index.ogan', [
            'title' => "Article #{$id}",
            'name' => "Démonstration : Article #{$id}",
            'user_count' => null,
            'features' => [
                [
                    'title' => "Article numéro {$id}",
                    'description' => "Cette route utilise {id} qui est automatiquement contraint à des nombres uniquement. Essayez /article/abc - cela donnera une erreur 404 !",
                    'image' => 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=500&q=80',
                    'link' => '/'
                ]
            ]
        ]);
    }

    /**
     * Route avec {slug} → détection automatique slug URL
     * Exemple: /post/mon-super-article
     */
    #[Route(path: '/post/{slug}', methods: ['GET'], name: 'post_show')]
    public function postShow(string $slug)
    {
        return $this->render('home/index.ogan', [
            'title' => "Post: {$slug}",
            'name' => "Démonstration : Post",
            'user_count' => null,
            'features' => [
                [
                    'title' => "Post: {$slug}",
                    'description' => "Cette route utilise {slug} qui accepte automatiquement les slugs URL (lettres minuscules, chiffres, tirets). Le slug reçu est : '{$slug}'",
                    'image' => 'https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?auto=format&fit=crop&w=500&q=80',
                    'link' => '/'
                ]
            ]
        ]);
    }

    /**
     * Route avec {query} → paramètre optionnel automatique
     * Exemple: /search ou /search/hello
     */
    #[Route(path: '/search/{query}', methods: ['GET'], name: 'search')]
    public function search(?string $query = null)
    {
        $message = $query 
            ? "Vous avez recherché : '{$query}'"
            : "Aucune recherche effectuée. Ajoutez un terme après /search/ !";

        return $this->render('home/index.ogan', [
            'title' => 'Recherche',
            'name' => 'Démonstration : Recherche',
            'user_count' => null,
            'features' => [
                [
                    'title' => 'Recherche optionnelle',
                    'description' => "Le paramètre {query} est automatiquement optionnel. {$message}",
                    'image' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=500&q=80',
                    'link' => '/'
                ]
            ]
        ]);
    }

    /**
     * Route avec {page} → numérique automatique
     * Exemple: /products/page/2
     */
    #[Route(path: '/products/page/{page}', methods: ['GET'], name: 'products_page')]
    public function productsPage(int $page)
    {
        return $this->render('home/index.ogan', [
            'title' => "Produits - Page {$page}",
            'name' => "Démonstration : Pagination",
            'user_count' => null,
            'features' => [
                [
                    'title' => "Page {$page}",
                    'description' => "Le paramètre {page} est automatiquement contraint aux nombres. Parfait pour la pagination !",
                    'image' => 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?auto=format&fit=crop&w=500&q=80',
                    'link' => '/'
                ]
            ]
        ]);
    }
}
