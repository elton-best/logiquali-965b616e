# Sidebar LOGIQUALI — Blade + Reicon — Checklist de vérification

> Pack cloné sur le PC : `/home/beg2/reicon` (2 676 icônes, MIT).
> Sidebar : 100 % Reicon (SVG inline), 0 Lucide, 0 dépendance payante.
> Navigation : sections RÉELLES LOGIQUALI (pas les routes fictives facturation/devis/proformas).

## Fichiers livrés (backend/)

- `resources/views/components/reicon.blade.php` — `<x-reicon name="widget" class="w-5 h-5" />` (55 tracés Outline extraits de `data/icon-data.json`).
- `resources/views/components/layout/sidebar.blade.php` — sidebar complète (props `user`, `isDemo`).
- `resources/views/components/layout/sidebar-link.blade.php` — lien (actif / highlight / badge / tooltip).
- `config/navigation.php` — source unique, 6 sections réelles, labels FR inchangés.
- `app/View/Composers/SidebarComposer.php` — actif + auto-ouverture + URL (enregistré dans `AppServiceProvider`).
- `resources/views/layouts/app.blade.php` — layout parent (`x-data` Alpine, hamburger, dark).
- `resources/views/dashboard/demo.blade.php` — vue démo (`Route::currentRouteName()`).
- `resources/css/sidebar-tokens.css` + `@theme` dans `app.css` — vars clair/sombre.
- `tailwind.config.sidebar.extract.js` — extrait v3 (`darkMode: 'class'`).
- `frontend/src/components/ReIcon.vue` + `frontend/src/plugins/vuetify.ts` — set Reicon officiel branché globalement sur Vuetify.
- `frontend/src/utils/reiconMap.ts` — mapping MDI/Lucide historique vers les noms Reicon.
- `frontend/src/compat/lucide-vue-next.ts` + alias Vite/TypeScript — compatibilité des imports Lucide historiques, rendu Reicon sans réécriture de tous les templates.
- `routes/web.php` — routes `dashboard.*` démo (à remplacer par contrôleurs).

## Mapping MDI/Lucide → Reicon (extrait)

| Avant (MDI / Lucide) | Après (Reicon) | Usage |
|---|---|---|
| `mdi-view-dashboard` / LayoutDashboard | `widget` | Vue d’ensemble |
| Plus | `add-circle` | Nouveau document (highlight) |
| `mdi-checkbox-marked` | `check-list` | Mes Tâches |
| `mdi-file-document` / FileText | `file-text` | Documents |
| `mdi-check-circle` / FileCheck | `check-circle` | Vérification |
| `mdi-check-decagram` | `verify` | Approbation |
| Tag | `tag2` | Nomenclature |
| Book / Palette | `book-open` | Bibliothèque normes |
| Diagram | `diagram-tree` | Processus |
| Alert | `alert-triangle` | Risques |
| Target | `target` | Objectifs |
| Calendar | `calendar-check` | Plans SM |
| Clipboard | `clipboard-check` | Audits |
| Presentation | `presentation` | Revue direction |
| Chart / BarChart3 | `chart-line` | Indicateurs |
| AlertCircle | `alert-circle` | Non-conformités |
| `mdi-office-building` / Building2 | `buildings` | Sites |
| Users | `users` | Collaborateurs |
| UserHeart | `user-heart` | Parties intéressées |
| Bullhorn | `bullhorn` | Communications |
| GraduationCap | `graduation-cap` | Formations |
| Settings | `settings` | Paramètres |
| Shield | `shield` | Journal sécurité |
| Crown / CreditCard | `crown` | Abonnement |
| User / Building2 | `building` | Profil Entreprise |
| ChevronDown/Left/Right | `chevron-down/left/right` | Accordéon + collapse |
| LogOut | `logout` | Déconnexion |
| Menu / X | `menu` / `close-circle` | Drawer mobile |

Choix justifié : SVG inline (pas `blade-ui-kit/blade-lucide-icons`) car Reicon n’a pas de package Blade officiel ; inline = zéro dépendance, `currentColor` hérité, taille via Tailwind, sans build.

## Checklist comportements

- [ ] **Actif** : `/dashboard` → seul « Vue d’ensemble » actif (`routeIs('dashboard')` strict) ; `/dashboard/documents/verification` → lien + section « Information documentée » actifs (`dashboard.documents.*`).
- [ ] **Accordéon** : clic header → ouvre/ferme avec transition ; « Général » non repliable ; état initial : tout ouvert sauf `config` fermé.
- [ ] **Auto-ouverture** : recharger sur `/dashboard/risks` → section « Pilotage » ouverte automatiquement (via `sidebarActiveSection`).
- [ ] **Collapse desktop** : bouton chevrons (visible `lg` uniquement) → 74 px icônes centrées + tooltips, 260 px élargi ; `localStorage logiquali-sidebar-collapsed` persisté.
- [ ] **Sections persistées** : replier/déplier → `localStorage logiquali-sidebar-sections` conservé après reload.
- [ ] **Mobile** : `< lg` → hamburger ouvre drawer 270 px + overlay flouté ; clic overlay ou lien ferme (`mobileOpen = false`) ; fermeture via `close-circle`.
- [ ] **Highlight** : « Nouveau document » fond vert clair (`--color-green`/10), pas blanc plein.
- [ ] **Badges** : `ISO` / `Pro` pastilles arrondies, fond blanc/20 si lien actif.
- [ ] **Logout** : formulaire POST + `@csrf` vers `logout` (remplace `supabase.auth.signOut()` + redirect `/login`).
- [ ] **Footer compte** : `?tab=compte` vers paramètres ; avatar initiale + dégradé vert + pastille en ligne ; mode réduit : avatar + logout empilés centrés.
- [ ] **Dark mode** : toggle ☀️/🌙 → `.dark` + `localStorage theme`, vérifier `dark:` (fonds `--color-card` sombre).
- [ ] **A11y** : `nav aria-label`, `aria-expanded`/`aria-controls` accordéon, `aria-current="page"`, `title`, `type="button"`, focus `outline green`.
- [ ] **Build** : `php artisan view:cache` OK, `php artisan route:list --name=dashboard` liste 32 routes (25 entrées sidebar), `npm run build` frontend OK, `npm audit` à 0 vulnérabilité.

## Commandes

```bash
cd backend
php artisan view:clear && php artisan view:cache
php artisan route:list --name=dashboard
php artisan tinker --execute="print_r(array_column(config('navigation'),'label'));"
npm run build # si Tailwind v4 (backend) / frontend Vite
```
