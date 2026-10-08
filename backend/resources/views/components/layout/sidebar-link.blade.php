{{--
  Lien de navigation sidebar (partiel).
  - "Créer/Nouveau document" (highlight) : fond vert clair.
  - Lien actif : fond vert #1A6B3C, texte blanc, ombre.
  - Badges : pastille arrondie, style adapté si actif.
  - Mode réduit : icône centrée + tooltip ; label masqué via Alpine.
  - Ferme le drawer mobile au clic (@click="mobileOpen = false").
  Variables : $item [route, label, icon, badge?, highlight?, url?, isActive?].
--}}

@php
    $isActive = (bool) ($item['isActive'] ?? false);
    $isHighlight = (bool) ($item['highlight'] ?? false);
    $icon = $item['icon'] ?? 'file-text';
    $label = $item['label'] ?? '';
    $url = $item['url'] ?? '#';
    $badge = $item['badge'] ?? null;

    // Classes d'état : actif > highlight > normal (clair + sombre via CSS vars).
    if ($isActive) {
        $linkClass = 'sb-link-active bg-[var(--color-green)] text-white shadow-md shadow-[var(--color-green)]/30';
        $badgeClass = 'bg-white/20 text-white';
    } elseif ($isHighlight) {
        $linkClass = 'sb-link-highlight bg-[var(--color-green)]/10 text-[var(--color-green)] hover:bg-[var(--color-green)]/15';
        $badgeClass = 'bg-[var(--color-green)]/15 text-[var(--color-green)]';
    } else {
        $linkClass = 'text-[var(--color-foreground)] hover:bg-[var(--color-surface-muted)]';
        $badgeClass = 'bg-[var(--color-surface-muted)] text-[var(--color-muted-foreground)]';
    }
@endphp

<li>
    <a
        href="{{ $url }}"
        @click="mobileOpen = false"
        aria-current="{{ $isActive ? 'page' : 'false' }}"
        aria-label="{{ $label }}"
        title="{{ $label }}"
        class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-green)] {{ $linkClass }}"
    >
        {{-- Icône Reicon (même jeu que les sections, currentColor hérité). --}}
        <span class="grid h-6 w-6 shrink-0 place-items-center mx-auto lg:mx-0" :class="{'mx-auto': collapsed}" aria-hidden="true">
            <x-reicon name="{{ $icon }}" class="h-5 w-5" />
        </span>

        {{-- Label : masqué en mode réduit, tronqué si long. --}}
        <span class="min-w-0 flex-1 truncate" x-show="!collapsed" x-transition.opacity>{{ $label }}</span>

        {{-- Badge (ex. ISO, Pro, OHADA, PDF) : masqué en réduit, adapté si actif. --}}
        @if($badge)
            <span
                x-show="!collapsed"
                class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide {{ $badgeClass }}"
            >{{ $badge }}</span>
        @endif

        {{-- Tooltip au survol en mode réduit (desktop uniquement). --}}
        <span
            x-show="collapsed"
            class="pointer-events-none absolute left-[62px] z-50 hidden whitespace-nowrap rounded-lg bg-[var(--color-foreground)] px-2 py-1 text-xs font-medium text-[var(--color-card)] group-hover:block"
            aria-hidden="true"
        >{{ $label }}</span>
    </a>
</li>
