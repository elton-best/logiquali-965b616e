@extends('pdf.layouts.master')

@section('title', 'Rapport de Revue de Direction')

@section('content')
@php
    $participants = is_array($review->participants) ? $review->participants : (json_decode($review->participants ?? '[]', true) ?: []);
    $kpiData = is_array($review->kpi_data) ? $review->kpi_data : (json_decode($review->kpi_data ?? '[]', true) ?: []);
    $actionsData = is_array($review->actions_data) ? $review->actions_data : (json_decode($review->actions_data ?? '[]', true) ?: []);
@endphp

<div class="header" style="text-align:center; margin-bottom:20px;">
    <h1>RAPPORT DE REVUE DE DIRECTION</h1>
    <p><strong>{{ $review->title ?? 'Revue de Direction' }}</strong></p>
</div>

<div class="section">
    <table style="width:100%; border-collapse:collapse; font-size:11pt;">
        <tr>
            <td style="width:35%; font-weight:bold; padding:5px; border:1px solid #ccc;">Référence</td>
            <td style="padding:5px; border:1px solid #ccc;">{{ $review->ref ?? '—' }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; padding:5px; border:1px solid #ccc;">Date planifiée</td>
            <td style="padding:5px; border:1px solid #ccc;">{{ $review->planned_date ? \Carbon\Carbon::parse($review->planned_date)->format('d/m/Y') : '—' }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; padding:5px; border:1px solid #ccc;">Date réelle</td>
            <td style="padding:5px; border:1px solid #ccc;">{{ $review->actual_date ? \Carbon\Carbon::parse($review->actual_date)->format('d/m/Y') : '—' }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; padding:5px; border:1px solid #ccc;">Président</td>
            <td style="padding:5px; border:1px solid #ccc;">{{ $review->chairman?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; padding:5px; border:1px solid #ccc;">Statut</td>
            <td style="padding:5px; border:1px solid #ccc;">{{ ucfirst($review->status ?? '—') }}</td>
        </tr>
    </table>
</div>

@if(count($participants) > 0)
<h2>Participants</h2>
<div class="section">
    <ul>
        @foreach($participants as $p)
        <li>{{ is_array($p) ? ($p['name'] ?? $p['label'] ?? json_encode($p)) : $p }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($review->context_changes)
<h2>Évolutions du contexte</h2>
<div class="section"><p>{{ $review->context_changes }}</p></div>
@endif

@if($review->customer_satisfaction)
<h2>Satisfaction client</h2>
<div class="section"><p>{{ $review->customer_satisfaction }}</p></div>
@endif

@if($review->audit_results)
<h2>Résultats des audits</h2>
<div class="section"><p>{{ $review->audit_results }}</p></div>
@endif

@if($review->nc_complaints_status)
<h2>État des non-conformités et réclamations</h2>
<div class="section"><p>{{ $review->nc_complaints_status }}</p></div>
@endif

@if($review->performance_indicators)
<h2>Indicateurs de performance</h2>
<div class="section"><p>{{ $review->performance_indicators }}</p></div>
@endif

@if($review->improvement_opportunities)
<h2>Opportunités d'amélioration</h2>
<div class="section"><p>{{ $review->improvement_opportunities }}</p></div>
@endif

@if($review->resources_adequacy)
<h2>Adéquation des ressources</h2>
<div class="section"><p>{{ $review->resources_adequacy }}</p></div>
@endif

@if($review->previous_actions_status)
<h2>Suivi des actions précédentes</h2>
<div class="section"><p>{{ $review->previous_actions_status }}</p></div>
@endif

@if(count($actionsData) > 0)
<h2>Plan d'actions</h2>
<div class="section">
    <table style="width:100%; border-collapse:collapse; font-size:10pt;">
        <tr style="background:#f0f0f0;">
            <th style="border:1px solid #ccc; padding:4px;">Action</th>
            <th style="border:1px solid #ccc; padding:4px;">Responsable</th>
            <th style="border:1px solid #ccc; padding:4px;">Échéance</th>
        </tr>
        @foreach($actionsData as $action)
        <tr>
            <td style="border:1px solid #ccc; padding:4px;">{{ is_array($action) ? ($action['description'] ?? $action['action'] ?? '—') : $action }}</td>
            <td style="border:1px solid #ccc; padding:4px;">{{ is_array($action) ? ($action['responsible'] ?? $action['responsable'] ?? '—') : '—' }}</td>
            <td style="border:1px solid #ccc; padding:4px;">{{ is_array($action) ? ($action['due_date'] ?? $action['echeance'] ?? '—') : '—' }}</td>
        </tr>
        @endforeach
    </table>
</div>
@endif

<div class="section" style="margin-top:40px; font-size:10pt; color:#666;">
    <p>Document généré le {{ now()->format('d/m/Y H:i') }}</p>
</div>
@endsection
