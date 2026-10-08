<?php

namespace App\View\Composers;

use Illuminate\View\View;

/**
 * Compositeur de la sidebar LOGIQUALI.
 *
 * Fournit $sidebarSections à la vue sidebar avec :
 *  - sections issues de config/navigation.php (source unique, sections réelles)
 *  - calcul d'état actif (équiv. isActive React : strict pour 'dashboard',
 *    sinon routeIs avec wildcard 'dashboard.documents.*')
 *  - auto-ouverture de la section contenant la route active
 *  - défauts d'ouverture (tout ouvert sauf 'config' fermé, comme l'énoncé)
 */
class SidebarComposer
{
    /**
     * Sections ouvertes par défaut (persistance localStorage côté Alpine en plus).
     *
     * @var array<string, bool>
     */
    public const DEFAULT_OPEN = [
        'general' => true, // non repliable, toujours visible
        'information-documentee' => true,
        'pilotage' => true,
        'evaluation' => true,
        'organisation' => true,
        'config' => false,
    ];

    /**
     * Lie les données de navigation à la vue.
     */
    public function compose(View $view): void
    {
        /** @var array<int, array> $sections */
        $sections = config('navigation', []);

        $activeSectionId = null;

        foreach ($sections as &$section) {
            $section['isActive'] = false;
            foreach ($section['items'] ?? [] as &$item) {
                $item['isActive'] = $this->isItemActive($item);
                $item['url'] = $this->resolveUrl($item);
                if ($item['isActive']) {
                    $section['isActive'] = true;
                    $activeSectionId = $section['id'];
                }
            }
            unset($item);
        }
        unset($section);

        $view->with([
            'sidebarSections' => $sections,
            'sidebarDefaultOpen' => self::DEFAULT_OPEN,
            'sidebarActiveSection' => $activeSectionId,
        ]);
    }

    /**
     * Équivalent Blade de isActive() React.
     * - route 'dashboard' : égalité stricte routeIs('dashboard')
     * - sinon : routeIs avec patterns (égalité OU préfixe via wildcard).
     */
    protected function isItemActive(array $item): bool
    {
        $patterns = $item['active'] ?? [$item['route'] ?? ''];

        foreach ($patterns as $pattern) {
            // Sécurité : pattern vide ignoré
            if ($pattern === '') {
                continue;
            }
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Résout l'URL : route nommée si elle existe, sinon '#'.
     * Évite les 500 si une route démo n'est pas encore déclarée.
     */
    protected function resolveUrl(array $item): string
    {
        $routeName = $item['route'] ?? null;

        if (! $routeName) {
            return '#';
        }

        // Paramètres de route optionnels (ex. ?tab=compte géré dans la vue)
        $params = $item['params'] ?? [];

        try {
            if (\Illuminate\Support\Facades\Route::has($routeName)) {
                return route($routeName, $params);
            }
        } catch (\Throwable $e) {
            // Retombe sur '#' en cas de route mal configurée
        }

        return '#';
    }
}
