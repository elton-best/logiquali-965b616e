<!DOCTYPE html>
{{-- Layout Blade LOGIQUALI — intègre la sidebar Reicon (desktop + mobile). --}}
<html lang="fr" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }" :class="{ 'dark': darkMode }" x-init="$watch('darkMode', v => localStorage.setItem('theme', v ? 'dark' : 'light'))">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LOGIQUALI — Tableau de bord')</title>

    {{-- Thème clair/sombre : appliqué avant peinture (évite le flash). --}}
    <script>
        try {
            if (localStorage.getItem('theme') === 'dark' ||
                (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    </script>

    {{-- Variables de couleurs sidebar (clair + sombre). --}}
    @vite(['resources/css/app.css', 'resources/css/sidebar-tokens.css', 'resources/js/app.js'])

    {{-- Alpine.js : seule interactivité (sans build JS custom). --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Anti-flash Alpine : masque les éléments x-cloak avant init. */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[var(--color-surface-muted)] text-[var(--color-foreground)] antialiased">

@php
    // Sections par défaut pour l'auto-ouverture côté Alpine (SSR + JS).
    $alpineDefaults = $sidebarDefaultOpen ?? config('navigation-defaults', []);
@endphp

{{-- État global sidebar : réduit/élargi + sections + drawer mobile + dark. --}}
<div
    x-data="{
        collapsed: JSON.parse(localStorage.getItem('logiquali-sidebar-collapsed') ?? 'false'),
        mobileOpen: false,
        openSections: JSON.parse(localStorage.getItem('logiquali-sidebar-sections') ?? '{}'),
        darkMode: document.documentElement.classList.contains('dark'),
        init() {
            // Valeurs par défaut serveur (tout ouvert sauf 'config').
            const defaults = @js($sidebarDefaultOpen ?? ['general' => true, 'information-documentee' => true, 'pilotage' => true, 'evaluation' => true, 'organisation' => true, 'config' => false]);
            for (const [id, open] of Object.entries(defaults)) {
                if (!(id in this.openSections)) this.openSections[id] = open;
            }
            // Auto-ouverture de la section contenant la route active.
            const active = @js($sidebarActiveSection ?? null);
            if (active) this.openSections[active] = true;
            // Persistance : réduit/élargi + sections.
            this.$watch('collapsed', v => localStorage.setItem('logiquali-sidebar-collapsed', JSON.stringify(v)));
            this.$watch('openSections', v => localStorage.setItem('logiquali-sidebar-sections', JSON.stringify(v)), { deep: true });
            this.$watch('darkMode', v => {
                document.documentElement.classList.toggle('dark', v);
                localStorage.setItem('theme', v ? 'dark' : 'light');
            });
        },
        toggleSection(id) { this.openSections[id] = !this.isOpen(id); },
        isOpen(id) { return !!this.openSections[id]; },
        toggleCollapse() { this.collapsed = !this.collapsed; }
    }"
    class="flex min-h-screen"
>
    {{-- Sidebar : drawer < lg (270px + overlay), fixe ≥ lg (74px / 260px). --}}
    <x-layout.sidebar :user="$user ?? null" :isDemo="$isDemo ?? false" />

    {{-- Colonne principale : topbar + contenu. --}}
    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Topbar : bouton hamburger mobile + toggle dark + slot actions. --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-[var(--color-border)] bg-[var(--color-card)]/90 px-4 backdrop-blur">
            {{-- Hamburger : géré par le layout parent (ouvre le drawer mobile). --}}
            <button
                type="button"
                @click="mobileOpen = true"
                class="rounded-lg p-2 text-[var(--color-muted-foreground)] hover:bg-[var(--color-surface-muted)] focus-visible:outline-2 focus-visible:outline-[var(--color-green)] lg:hidden"
                aria-label="Ouvrir le menu"
                title="Ouvrir le menu"
            >
                <x-reicon name="menu" class="h-6 w-6" />
            </button>

            <div class="min-w-0 flex-1">
                <h1 class="truncate text-base font-bold">@yield('header', 'Tableau de bord')</h1>
                <p class="truncate text-xs text-[var(--color-muted-foreground)]">@yield('subheader', 'Pilotage QHSE — ISO 9001')</p>
            </div>

            {{-- Bascule dark mode (conserve les variantes dark:). --}}
            <button
                type="button"
                @click="darkMode = !darkMode"
                class="rounded-lg p-2 text-[var(--color-muted-foreground)] hover:bg-[var(--color-surface-muted)] focus-visible:outline-2 focus-visible:outline-[var(--color-green)]"
                :aria-label="darkMode ? 'Passer en mode clair' : 'Passer en mode sombre'"
                title="Basculer clair/sombre"
            >
                <span x-show="!darkMode">🌙</span>
                <span x-show="darkMode" x-cloak>☀️</span>
            </button>

            @yield('topbar-actions')
        </header>

        {{-- Contenu de la page. --}}
        <main class="min-w-0 flex-1 p-4 lg:p-6" id="main-content" tabindex="-1">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
