{{-- Vue démo pour tester la sidebar (route active, accordéon, collapse, mobile). --}}
@extends('layouts.app')

@section('title', 'LOGIQUALI — Démo sidebar Reicon')
@section('header', 'Démo sidebar')
@section('subheader')
    Route active : {{ Route::currentRouteName() }} — testez l’accordéon, le mode réduit et le drawer mobile.
@endsection

@section('content')
    <div class="mx-auto max-w-3xl space-y-4">
        <div class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-card)] p-6">
            <h2 class="text-lg font-bold">Sidebar LOGIQUALI + Reicon</h2>
            <p class="mt-2 text-sm text-[var(--color-muted-foreground)]">
                Navigation réelle du projet (Information documentée, Pilotage, Évaluation, Sites &amp; Équipes).
                Icônes 100&nbsp;% Reicon (<code class="rounded bg-[var(--color-surface-muted)] px-1">&lt;x-reicon …/&gt;</code>),
                pack cloné dans <code class="rounded bg-[var(--color-surface-muted)] px-1">/home/beg2/reicon</code>.
            </p>
            <ul class="mt-4 list-disc space-y-1 pl-5 text-sm">
                <li>Lien actif : fond vert <code>#1A6B3C</code>, texte blanc, ombre.</li>
                <li>«&nbsp;Nouveau document&nbsp;» en avant : fond vert clair.</li>
                <li>Badges : <x-reicon name="book-open" class="inline h-4 w-4" /> ISO, <x-reicon name="crown" class="inline h-4 w-4" /> Pro.</li>
                <li>Réduit 74&nbsp;px / élargi 260&nbsp;px (bouton chevrons, desktop).</li>
                <li>Mobile : drawer 270&nbsp;px + overlay flouté (bouton hamburger du layout).</li>
                <li>Persistance : <code>localStorage</code> (réduit/élargi + sections).</li>
                <li>Footer : avatar initiale + pastille en ligne + logout POST + @csrf.</li>
            </ul>
        </div>

        <div class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-card)] p-6 text-sm">
            <p><strong>Route courante :</strong> <code>{{ Route::currentRouteName() }}</code></p>
            <p><strong>URL :</strong> <code>{{ request()->url() }}</code></p>
            <p class="mt-2 text-[var(--color-muted-foreground)]">Changez de page via la sidebar pour vérifier l’état actif et l’auto-ouverture de section.</p>
        </div>
    </div>
@endsection
