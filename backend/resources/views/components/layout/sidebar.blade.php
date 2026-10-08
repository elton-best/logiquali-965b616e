{{--
  Sidebar LOGIQUALI — Blade + Tailwind + Alpine (sans build JS custom)
  --------------------------------------------------------------------
  - Reproduction du comportement React : accordéon, lien actif, highlight,
    badges, réduit 74px / élargi 260px / drawer mobile 270px + overlay.
  - Navigation RÉELLE du projet (config/navigation.php), pas les routes
    fictives facturation/devis/proformas (voir config/navigation.php).
  - Icônes 100% Reicon via <x-reicon> (SVG inline, currentColor).
    Pack cloné dans /home/beg2/reicon (MIT). Aucune dépendance payante.
  - Alpine : consomme le scope parent (openSections, collapsed, mobileOpen,
    toggleSection(), isOpen()). Le x-data est déclaré dans layouts/app.blade.php.
  - Props : $user (défaut auth()->user()), $isDemo (optionnel).
  - Accessibilité : nav aria-label, boutons type="button", aria-expanded,
    title + tooltip en mode réduit, focus-visible via CSS.
  - Dark mode : variantes dark: + variables CSS (voir sidebar-tokens.css).
--}}

@props([
    'user' => null,
    'isDemo' => false,
])

@php
    // Utilisateur courant (nom, e-mail, initiale pour l'avatar).
    $currentUser = $user ?? auth()->user();
    $userName = $currentUser->name ?? $currentUser->full_name ?? $currentUser->email ?? 'Utilisateur';
    $userEmail = $currentUser->email ?? '';
    // Initiale (support UTF-8).
    $initial = mb_strtoupper(mb_substr(trim($userName) !== '' ? trim($userName) : 'U', 0, 1));
    // Sections fournies par le SidebarComposer (avec isActive + url déjà calculés).
    $sections = $sidebarSections ?? config('navigation', []);
    $defaultOpen = $sidebarDefaultOpen ?? [];
    $activeSection = $sidebarActiveSection ?? null;
@endphp

{{-- Overlay mobile : fond noir flouté, ferme le drawer au clic. Desktop : caché. --}}
<div
    x-show="mobileOpen"
    x-transition.opacity
    @click="mobileOpen = false"
    class="fixed inset-0 z-30 bg-black/50 backdrop-blur-sm lg:hidden"
    aria-hidden="true"
></div>

{{-- Conteneur sidebar : drawer mobile + colonne fixe desktop. --}}
<aside
    id="app-sidebar"
    aria-label="Navigation principale"
    class="fixed inset-y-0 left-0 z-40 flex w-[270px] flex-col border-r border-[var(--color-border)] bg-[var(--color-card)] transition-all duration-300 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:shrink-0"
    :class="{
        'translate-x-0': mobileOpen,
        '-translate-x-full': !mobileOpen,
        'lg:translate-x-0': true,
        'lg:w-[260px]': !collapsed,
        'lg:w-[74px]': collapsed
    }"
>
    {{-- En-tête marque : 64px, bordure basse, logo + texte (icône seule en réduit). --}}
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-[var(--color-border)] px-4">
        {{-- Logo : image entreprise si dispo, sinon marque Reicon (building). --}}
        <a
            href="{{ \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/dashboard') }}"
            class="flex min-w-0 flex-1 items-center gap-3 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-green)]"
            aria-label="Accueil — Tableau de bord"
            title="Tableau de bord"
            @click="mobileOpen = false"
        >
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[var(--color-green)] text-white shadow-md">
                <x-reicon name="buildings" class="h-5 w-5" />
            </span>
            {{-- Texte masqué en mode réduit (desktop) via Alpine. --}}
            <span class="min-w-0" x-show="!collapsed" x-transition.opacity>
                <span class="block truncate text-sm font-bold text-[var(--color-foreground)]">LOGIQUALI</span>
                <span class="block truncate text-xs text-[var(--color-muted-foreground)]">QHSE · ISO 9001</span>
            </span>
        </a>

        {{-- Bouton toggle réduit/élargi : desktop uniquement, chevrons Reicon. --}}
        <button
            type="button"
            @click="toggleCollapse()"
            class="hidden shrink-0 rounded-lg p-2 text-[var(--color-muted-foreground)] hover:bg-[var(--color-surface-muted)] hover:text-[var(--color-foreground)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-green)] lg:inline-flex"
            :aria-label="collapsed ? 'Élargir la sidebar' : 'Réduire la sidebar'"
            :title="collapsed ? 'Élargir' : 'Réduire'"
        >
            <span x-show="!collapsed"><x-reicon name="chevron-left" class="h-5 w-5" /></span>
            <span x-show="collapsed"><x-reicon name="chevron-right" class="h-5 w-5" /></span>
        </button>

        {{-- Bouton fermer : mobile uniquement. --}}
        <button
            type="button"
            @click="mobileOpen = false"
            class="shrink-0 rounded-lg p-2 text-[var(--color-muted-foreground)] hover:bg-[var(--color-surface-muted)] focus-visible:outline-2 focus-visible:outline-[var(--color-green)] lg:hidden"
            aria-label="Fermer le menu"
            title="Fermer"
        >
            <x-reicon name="close-circle" class="h-5 w-5" />
        </button>
    </div>

    {{-- Bandeau démo (optionnel, prop isDemo). --}}
    @if($isDemo)
        <div class="mx-3 mt-3 rounded-lg border border-dashed border-[var(--color-gold)] bg-[var(--color-gold)]/10 px-3 py-2 text-xs font-semibold text-[var(--color-foreground)]" x-show="!collapsed">
            Mode démo — données fictives
        </div>
    @endif

    {{-- Navigation scrollable. --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Menu principal">
        @foreach($sections as $section)
            @php
                $sectionId = $section['id'];
                $isCollapsible = (bool) ($section['collapsible'] ?? true);
                $sectionIcon = $section['icon'] ?? 'file-text';
                $hasActiveChild = (bool) ($section['isActive'] ?? false);
            @endphp

            <div class="mb-1" data-section="{{ $sectionId }}">
                @if(! $isCollapsible)
                    {{-- Section "Général" : pas d'accordéon, label discret + items directs. --}}
                    <p
                        class="mb-2 px-2 text-[11px] font-bold uppercase tracking-wider text-[var(--color-muted-foreground)]"
                        x-show="!collapsed"
                    >{{ $section['label'] }}</p>

                    <ul class="space-y-1">
                        @foreach($section['items'] as $item)
                            @include('components.layout.sidebar-link', ['item' => $item])
                        @endforeach
                    </ul>
                @else
                    {{-- En-tête de section repliable (bouton accordéon). --}}
                    <button
                        type="button"
                        @click="toggleSection('{{ $sectionId }}')"
                        :aria-expanded="isOpen('{{ $sectionId }}').toString()"
                        aria-controls="section-{{ $sectionId }}"
                        aria-label="Section {{ $section['label'] }}"
                        title="{{ $section['label'] }}"
                        class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-green)] {{ $hasActiveChild ? 'text-[var(--color-green)]' : 'text-[var(--color-foreground)] hover:bg-[var(--color-surface-muted)]' }}"
                    >
                        {{-- Icône centrée en mode réduit, avec tooltip natif + custom. --}}
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg {{ $hasActiveChild ? 'bg-[var(--color-green)]/10' : 'bg-transparent group-hover:bg-[var(--color-green)]/10' }} mx-auto lg:mx-0" :class="{'mx-auto': collapsed}">
                            <x-reicon name="{{ $sectionIcon }}" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1 truncate text-left" x-show="!collapsed" x-transition.opacity>{{ $section['label'] }}</span>
                        {{-- Chevron rotatif selon l'état ouvert/fermé. --}}
                        <span
                            class="shrink-0 transition-transform duration-200"
                            x-show="!collapsed"
                            :class="{'rotate-180': isOpen('{{ $sectionId }}')}"
                        >
                            <x-reicon name="chevron-down" class="h-4 w-4" />
                        </span>
                        {{-- Tooltip en mode réduit (desktop). --}}
                        <span
                            x-show="collapsed"
                            class="sb-tooltip pointer-events-none absolute left-[70px] z-50 hidden rounded-lg bg-[var(--color-foreground)] px-2 py-1 text-xs font-medium text-[var(--color-card)] group-hover:block"
                            aria-hidden="true"
                        >{{ $section['label'] }}</span>
                    </button>

                    {{-- Items de la section (accordéon + auto-ouverture si route active). --}}
                    <div
                        id="section-{{ $sectionId }}"
                        x-show="isOpen('{{ $sectionId }}')"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="mt-1 overflow-hidden"
                    >
                        <ul class="space-y-1" :class="{'lg:pl-0': collapsed, 'lg:pl-3': !collapsed}">
                            @foreach($section['items'] as $item)
                                @include('components.layout.sidebar-link', ['item' => $item])
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endforeach
    </nav>

    {{-- Footer profil : carte arrondie, avatar initiale + pastille en ligne, logout POST. --}}
    <div class="shrink-0 border-t border-[var(--color-border)] p-3">
        {{-- Mode élargi : carte complète. --}}
        <div class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface-muted)]/50 p-3" x-show="!collapsed" x-transition.opacity>
            <div class="flex items-center gap-3">
                {{-- Avatar : initiale, dégradé vert, pastille "en ligne". --}}
                <span class="relative grid h-10 w-10 shrink-0 place-items-center rounded-full bg-gradient-to-br from-[var(--color-green)] to-[var(--color-green-dark)] text-sm font-bold text-white" aria-hidden="true">
                    {{ $initial }}
                    <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-[var(--color-card)] bg-green-500" title="En ligne"></span>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-[var(--color-foreground)]" title="{{ $userName }}">{{ $userName }}</span>
                    <span class="block truncate text-xs text-[var(--color-muted-foreground)]" title="{{ $userEmail }}">{{ $userEmail }}</span>
                </span>
                {{-- Raccourci paramètres du compte (?tab=compte). --}}
                <a
                    href="{{ \Illuminate\Support\Facades\Route::has('dashboard.settings') ? route('dashboard.settings', ['tab' => 'compte']) : url('/dashboard/settings?tab=compte') }}"
                    class="rounded-lg p-2 text-[var(--color-muted-foreground)] hover:bg-[var(--color-card)] hover:text-[var(--color-green)] focus-visible:outline-2 focus-visible:outline-[var(--color-green)]"
                    aria-label="Paramètres du compte"
                    title="Paramètres du compte"
                    @click="mobileOpen = false"
                >
                    <x-reicon name="settings" class="h-5 w-5" />
                </a>
            </div>
            {{-- Bouton déconnexion : POST + CSRF (remplace supabase.auth.signOut()). --}}
            <form method="POST" action="{{ \Illuminate\Support\Facades\Route::has('logout') ? route('logout') : url('/logout') }}" class="mt-3">
                @csrf
                <button
                    type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 transition-colors hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/40"
                    aria-label="Se déconnecter"
                    title="Se déconnecter"
                >
                    <x-reicon name="logout" class="h-4 w-4" />
                    Se déconnecter
                </button>
            </form>
        </div>

        {{-- Mode réduit (desktop) : avatar + logout empilés et centrés. --}}
        <div class="hidden flex-col items-center gap-2" :class="{'lg:flex': collapsed, 'lg:hidden': !collapsed}">
            <span class="relative grid h-10 w-10 place-items-center rounded-full bg-gradient-to-br from-[var(--color-green)] to-[var(--color-green-dark)] text-sm font-bold text-white" title="{{ $userName }} — {{ $userEmail }}">
                {{ $initial }}
                <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-[var(--color-card)] bg-green-500" title="En ligne"></span>
            </span>
            <form method="POST" action="{{ \Illuminate\Support\Facades\Route::has('logout') ? route('logout') : url('/logout') }}">
                @csrf
                <button
                    type="submit"
                    class="grid h-10 w-10 place-items-center rounded-xl border border-red-200 text-red-600 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-red-500 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/40"
                    aria-label="Se déconnecter"
                    title="Se déconnecter"
                >
                    <x-reicon name="logout" class="h-5 w-5" />
                </button>
            </form>
        </div>

        {{-- Mobile : le footer complet reste visible (collapsed ne s'applique qu'en lg). --}}
        <div class="mt-2 lg:hidden">
            <form method="POST" action="{{ \Illuminate\Support\Facades\Route::has('logout') ? route('logout') : url('/logout') }}">
                @csrf
                <button
                    type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-red-500 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/40"
                    aria-label="Se déconnecter"
                >
                    <x-reicon name="logout" class="h-4 w-4" />
                    Se déconnecter
                </button>
            </form>
        </div>
    </div>
</aside>
