<?php

declare(strict_types=1);

namespace Ogan\Database\Pagination;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 📄 PAGINATOR - Gestion de la pagination
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Encapsule les résultats paginés et fournit des méthodes pour naviguer
 * entre les pages et générer les liens de pagination.
 * 
 * @example
 * $users = User::paginate(15);
 * foreach ($users as $user) { ... }
 * echo $users->links();
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */
class Paginator implements IteratorAggregate, Countable
{
    /** @var array Les éléments de la page courante */
    protected array $items;

    /** @var int Nombre total d'éléments */
    protected int $total;

    /** @var int Nombre d'éléments par page */
    protected int $perPage;

    /** @var int Page courante */
    protected int $currentPage;

    /** @var string Nom du paramètre de page dans l'URL */
    protected string $pageName = 'page';

    /** @var string|null URL de base pour les liens */
    protected ?string $path = null;

    /**
     * Crée un nouveau Paginator
     */
    public function __construct(array $items, int $total, int $perPage, int $currentPage = 1)
    {
        $this->items = $items;
        $this->total = max(0, $total);
        $this->perPage = max(1, $perPage);
        $this->currentPage = max(1, $currentPage);
        
        // Détecte automatiquement le path depuis la requête courante
        $this->path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // GETTERS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Retourne les éléments de la page courante
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Nombre total d'éléments
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * Nombre d'éléments par page
     */
    public function perPage(): int
    {
        return $this->perPage;
    }

    /**
     * Numéro de la page courante
     */
    public function currentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Numéro de la dernière page
     */
    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    /**
     * Nombre d'éléments sur cette page
     */
    public function count(): int
    {
        return count($this->items);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // NAVIGATION
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Y a-t-il des pages supplémentaires ?
     */
    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage();
    }

    /**
     * Y a-t-il des pages précédentes ?
     */
    public function hasPreviousPages(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Est-on sur la première page ?
     */
    public function onFirstPage(): bool
    {
        return $this->currentPage <= 1;
    }

    /**
     * Est-on sur la dernière page ?
     */
    public function onLastPage(): bool
    {
        return $this->currentPage >= $this->lastPage();
    }

    /**
     * Index du premier élément affiché
     */
    public function firstItem(): ?int
    {
        if ($this->total === 0) {
            return null;
        }
        return ($this->currentPage - 1) * $this->perPage + 1;
    }

    /**
     * Index du dernier élément affiché
     */
    public function lastItem(): ?int
    {
        if ($this->total === 0) {
            return null;
        }
        return min($this->firstItem() + $this->count() - 1, $this->total);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // GÉNÉRATION D'URLS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Génère l'URL pour une page donnée
     */
    public function url(int $page): string
    {
        $page = max(1, $page);
        
        // Récupère les paramètres GET existants
        $query = $_GET;
        $query[$this->pageName] = $page;
        
        return $this->path . '?' . http_build_query($query);
    }

    /**
     * URL de la page précédente
     */
    public function previousPageUrl(): ?string
    {
        if ($this->currentPage > 1) {
            return $this->url($this->currentPage - 1);
        }
        return null;
    }

    /**
     * URL de la page suivante
     */
    public function nextPageUrl(): ?string
    {
        if ($this->hasMorePages()) {
            return $this->url($this->currentPage + 1);
        }
        return null;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RENDU HTML
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Génère le HTML des liens de pagination (Tailwind CSS)
     * 
     * @param string|null $template Nom du template à utiliser (default, simple, htmx) ou chemin complet
     * @return string HTML de la pagination
     */
    public function links(?string $template = null): string
    {
        if ($this->lastPage() <= 1) {
            return '';
        }

        // Si un template est spécifié et existe, l'utiliser
        if ($template !== null) {
            return $this->renderTemplate($template);
        }

        // Rendu par défaut (Tailwind inline)
        return $this->renderDefaultTemplate();
    }

    /**
     * Génère le HTML avec attributs HTMX intégrés
     * 
     * @param string $target Sélecteur CSS de l'élément cible (#content, #user-list, etc.)
     * @param string $swap Type de swap HTMX (innerHTML, outerHTML, etc.)
     * @return string HTML de la pagination avec attributs HTMX
     */
    public function linksHtmx(string $target = '#content', string $swap = 'innerHTML'): string
    {
        if ($this->lastPage() <= 1) {
            return '';
        }

        $hxAttrs = sprintf('hx-target="%s" hx-swap="%s" hx-push-url="true"', 
            htmlspecialchars($target), 
            htmlspecialchars($swap)
        );

        $html = '<nav class="flex items-center justify-between">';
        
        // Info résumé
        $html .= '<div class="hidden sm:block text-sm text-gray-500">';
        $html .= sprintf(
            'Affichage de <span class="font-medium">%d</span> à <span class="font-medium">%d</span> sur <span class="font-medium">%d</span> résultats',
            $this->firstItem() ?? 0,
            $this->lastItem() ?? 0,
            $this->total
        );
        $html .= '</div>';

        // Boutons de pagination
        $html .= '<div class="flex gap-1">';
        
        // Bouton Précédent
        if ($this->hasPreviousPages()) {
            $html .= sprintf(
                '<a href="%s" hx-get="%s" %s class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">← Précédent</a>',
                htmlspecialchars($this->previousPageUrl()),
                htmlspecialchars($this->previousPageUrl()),
                $hxAttrs
            );
        } else {
            $html .= '<span class="px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">← Précédent</span>';
        }

        // Numéros de pages
        $html .= $this->renderPageNumbersHtmx($hxAttrs);

        // Bouton Suivant
        if ($this->hasMorePages()) {
            $html .= sprintf(
                '<a href="%s" hx-get="%s" %s class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">Suivant →</a>',
                htmlspecialchars($this->nextPageUrl()),
                htmlspecialchars($this->nextPageUrl()),
                $hxAttrs
            );
        } else {
            $html .= '<span class="px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">Suivant →</span>';
        }

        $html .= '</div>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Rendu du template par défaut (inline Tailwind)
     */
    protected function renderDefaultTemplate(): string
    {
        $html = '<nav class="flex items-center justify-between">';
        
        // Info résumé
        $html .= '<div class="hidden sm:block text-sm text-gray-500">';
        $html .= sprintf(
            'Affichage de <span class="font-medium">%d</span> à <span class="font-medium">%d</span> sur <span class="font-medium">%d</span> résultats',
            $this->firstItem() ?? 0,
            $this->lastItem() ?? 0,
            $this->total
        );
        $html .= '</div>';

        // Boutons de pagination
        $html .= '<div class="flex gap-1">';
        
        // Bouton Précédent
        if ($this->hasPreviousPages()) {
            $html .= sprintf(
                '<a href="%s" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">← Précédent</a>',
                htmlspecialchars($this->previousPageUrl())
            );
        } else {
            $html .= '<span class="px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">← Précédent</span>';
        }

        // Numéros de pages (affiche max 7 pages)
        $html .= $this->renderPageNumbers();

        // Bouton Suivant
        if ($this->hasMorePages()) {
            $html .= sprintf(
                '<a href="%s" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Suivant →</a>',
                htmlspecialchars($this->nextPageUrl())
            );
        } else {
            $html .= '<span class="px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">Suivant →</span>';
        }

        $html .= '</div>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Rendu via un fichier template externe
     */
    protected function renderTemplate(string $template): string
    {
        $projectRoot = dirname(__DIR__, 3);
        $templatesDir = $projectRoot . '/templates';
        $paginationDir = $templatesDir . '/pagination/';
        
        // Si c'est un nom court, ajouter le chemin et l'extension
        if (!str_contains($template, '/') && !str_ends_with($template, '.ogan')) {
            $templatePath = $paginationDir . $template . '.ogan';
            $relativePath = 'pagination/' . $template . '.ogan';
        } else {
            $templatePath = $template;
            $relativePath = str_replace($templatesDir . '/', '', $template);
        }

        if (!file_exists($templatePath)) {
            // Fallback au template par défaut
            return $this->renderDefaultTemplate();
        }

        // Créer une instance de View et rendre le template avec chemin relatif
        $view = new \Ogan\View\View($templatesDir, true);
        return $view->render($relativePath, ['paginator' => $this]);
    }

    /**
     * Génère les numéros de pages cliquables
     */
    public function renderPageNumbers(): string
    {
        $html = '';
        $lastPage = $this->lastPage();
        
        // Calcule la plage de pages à afficher
        $start = max(1, $this->currentPage - 2);
        $end = min($lastPage, $this->currentPage + 2);

        // Ajuste pour toujours afficher 5 pages si possible
        if ($end - $start < 4) {
            if ($start === 1) {
                $end = min($lastPage, 5);
            } elseif ($end === $lastPage) {
                $start = max(1, $lastPage - 4);
            }
        }

        // Première page + ellipsis si nécessaire
        if ($start > 1) {
            $html .= $this->renderPageLink(1);
            if ($start > 2) {
                $html .= '<span class="px-3 py-2 text-sm text-gray-500">...</span>';
            }
        }

        // Pages du milieu
        for ($page = $start; $page <= $end; $page++) {
            $html .= $this->renderPageLink($page);
        }

        // Dernière page + ellipsis si nécessaire
        if ($end < $lastPage) {
            if ($end < $lastPage - 1) {
                $html .= '<span class="px-3 py-2 text-sm text-gray-500">...</span>';
            }
            $html .= $this->renderPageLink($lastPage);
        }

        return $html;
    }

    /**
     * Génère les numéros de pages cliquables avec attributs HTMX (méthode publique)
     * 
     * @param string $target Sélecteur CSS cible
     * @param string $swap Type de swap HTMX
     */
    public function linksPageNumbersHtmx(string $target = '#content', string $swap = 'innerHTML'): string
    {
        $hxAttrs = sprintf('hx-target="%s" hx-swap="%s" hx-push-url="true"', 
            htmlspecialchars($target), 
            htmlspecialchars($swap)
        );
        return $this->renderPageNumbersHtmx($hxAttrs);
    }

    /**
     * Génère les numéros de pages cliquables avec attributs HTMX
     */
    protected function renderPageNumbersHtmx(string $hxAttrs): string
    {
        $html = '';
        $lastPage = $this->lastPage();
        
        // Calcule la plage de pages à afficher
        $start = max(1, $this->currentPage - 2);
        $end = min($lastPage, $this->currentPage + 2);

        // Ajuste pour toujours afficher 5 pages si possible
        if ($end - $start < 4) {
            if ($start === 1) {
                $end = min($lastPage, 5);
            } elseif ($end === $lastPage) {
                $start = max(1, $lastPage - 4);
            }
        }

        // Première page + ellipsis si nécessaire
        if ($start > 1) {
            $html .= $this->renderPageLinkHtmx(1, $hxAttrs);
            if ($start > 2) {
                $html .= '<span class="px-3 py-2 text-sm text-gray-500">...</span>';
            }
        }

        // Pages du milieu
        for ($page = $start; $page <= $end; $page++) {
            $html .= $this->renderPageLinkHtmx($page, $hxAttrs);
        }

        // Dernière page + ellipsis si nécessaire
        if ($end < $lastPage) {
            if ($end < $lastPage - 1) {
                $html .= '<span class="px-3 py-2 text-sm text-gray-500">...</span>';
            }
            $html .= $this->renderPageLinkHtmx($lastPage, $hxAttrs);
        }

        return $html;
    }

    /**
     * Génère un lien de page individuel avec HTMX
     */
    protected function renderPageLinkHtmx(int $page, string $hxAttrs): string
    {
        if ($page === $this->currentPage) {
            return sprintf(
                '<span class="px-3 py-2 text-sm font-medium text-white bg-indigo-600 border border-indigo-600 rounded-md">%d</span>',
                $page
            );
        }

        return sprintf(
            '<a href="%s" hx-get="%s" %s class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">%d</a>',
            htmlspecialchars($this->url($page)),
            htmlspecialchars($this->url($page)),
            $hxAttrs,
            $page
        );
    }

    /**
     * Génère un lien de page individuel
     */
    protected function renderPageLink(int $page): string
    {
        if ($page === $this->currentPage) {
            return sprintf(
                '<span class="px-3 py-2 text-sm font-medium text-white bg-indigo-600 border border-indigo-600 rounded-md">%d</span>',
                $page
            );
        }

        return sprintf(
            '<a href="%s" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">%d</a>',
            htmlspecialchars($this->url($page)),
            $page
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // INTERFACES
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Permet d'itérer directement sur le Paginator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Conversion en tableau
     */
    public function toArray(): array
    {
        return [
            'data' => $this->items,
            'current_page' => $this->currentPage,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'last_page' => $this->lastPage(),
            'first_item' => $this->firstItem(),
            'last_item' => $this->lastItem(),
        ];
    }
}
