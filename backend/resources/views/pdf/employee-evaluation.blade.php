@extends('pdf.layouts.master')

@section('title', 'Évaluation Employé - ' . ($anonymize ? 'Anonyme' : ($evaluation->employee->name ?? 'N/A')))

@section('styles')
    <style>
        .meta-info {
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #FF6600;
        }
        .meta-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-info td {
            padding: 5px;
            font-size: 9pt;
        }
        .meta-info td.label {
            font-weight: bold;
            width: 35%;
            color: #555;
        }
        .section-title {
            background-color: #2E3B55;
            color: white;
            padding: 8px 12px;
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 10px;
        }
        table.evaluation-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.evaluation-table th {
            background-color: #4472C4;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
            border: 1px solid #2E3B55;
        }
        table.evaluation-table td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 9pt;
        }
        .rating {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
        }
        .rating-1 { color: #D32F2F; }
        .rating-2 { color: #F57C00; }
        .rating-3 { color: #FBC02D; }
        .rating-4 { color: #7CB342; }
        .rating-5 { color: #388E3C; }
        .rgpd-notice {
            background-color: #FFF3CD;
            border-left: 4px solid #FFC107;
            padding: 10px;
            margin-top: 20px;
            font-size: 8pt;
            color: #856404;
        }
    </style>
@endsection

@section('content')
    <div class="header">
        <h1>FICHE D'ÉVALUATION EMPLOYÉ</h1>
        <div class="subtitle" style="font-size: 11pt; color: #666;">
            @if($anonymize)
                <strong style="color: #FF6600;">VERSION ANONYME - RGPD</strong>
            @else
                Évaluation annuelle du personnel - Confidentiel
            @endif
        </div>
    </div>

    <div class="meta-info">
        <table>
            <tr>
                <td class="label">Collaborateur:</td>
                <td>{{ $anonymize ? 'ANONYME (ID: ' . $evaluation->id . ')' : ($evaluation->employee->name ?? 'N/A') }}</td>
                <td class="label">Poste:</td>
                <td>{{ $anonymize ? 'CONFIDENTIEL' : ($evaluation->position ?? 'N/A') }}</td>
            </tr>
            <tr>
                <td class="label">Service/Département:</td>
                <td>{{ $anonymize ? 'CONFIDENTIEL' : ($evaluation->department ?? 'N/A') }}</td>
                <td class="label">Date d'évaluation:</td>
                <td>{{ $evaluation->evaluation_date?->format('d/m/Y') ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Évaluateur:</td>
                <td>{{ $anonymize ? 'ANONYME' : ($evaluation->evaluator?->name ?? 'N/A') }}</td>
                <td class="label">Période évaluée:</td>
                <td>{{ $evaluation->period ?? 'Année ' . now()->year }}</td>
            </tr>
        </table>
    </div>

    <div class="section" style="page-break-inside: avoid;">
        <div class="section-title">1. COMPÉTENCES TECHNIQUES</div>
        <table class="evaluation-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Critère</th>
                    <th style="width: 15%;">Note /5</th>
                    <th style="width: 35%;">Commentaire</th>
                </tr>
            </thead>
            <tbody>
                @foreach($evaluation->technical_skills ?? [] as $skill => $data)
                <tr>
                    <td>{{ $skill }}</td>
                    <td class="rating rating-{{ $data['rating'] ?? 3 }}">{{ $data['rating'] ?? 'N/A' }} / 5</td>
                    <td>{{ $anonymize ? '***' : ($data['comment'] ?? '-') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section" style="page-break-inside: avoid;">
        <div class="section-title">2. COMPÉTENCES COMPORTEMENTALES</div>
        <table class="evaluation-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Critère</th>
                    <th style="width: 15%;">Note /5</th>
                    <th style="width: 35%;">Commentaire</th>
                </tr>
            </thead>
            <tbody>
                @foreach($evaluation->behavioral_skills ?? [] as $skill => $data)
                <tr>
                    <td>{{ $skill }}</td>
                    <td class="rating rating-{{ $data['rating'] ?? 3 }}">{{ $data['rating'] ?? 'N/A' }} / 5</td>
                    <td>{{ $anonymize ? '***' : ($data['comment'] ?? '-') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section" style="page-break-inside: avoid;">
        <div class="section-title">3. ATTEINTE DES OBJECTIFS</div>
        <table class="evaluation-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Objectif</th>
                    <th style="width: 15%;">Cible</th>
                    <th style="width: 15%;">Réalisé</th>
                    <th style="width: 30%;">Commentaire</th>
                </tr>
            </thead>
            <tbody>
                @foreach($evaluation->objectives ?? [] as $objective)
                <tr>
                    <td>{{ $objective['title'] ?? 'N/A' }}</td>
                    <td class="rating">{{ $objective['target'] ?? 'N/A' }}</td>
                    <td class="rating rating-{{ $objective['achievement'] >= 80 ? 5 : ($objective['achievement'] >= 50 ? 3 : 1) }}">
                        {{ $objective['achievement'] ?? 'N/A' }}%
                    </td>
                    <td>{{ $anonymize ? '***' : ($objective['comment'] ?? '-') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section" style="page-break-inside: avoid;">
        <div class="section-title">4. PLAN DE DÉVELOPPEMENT</div>
        <p style="margin: 10px 0;">
            @if($anonymize)
                <em>Contenu anonymisé - Confidentiel RGPD</em>
            @else
                {!! nl2br(e($evaluation->development_plan ?? 'Aucun plan de développement défini.')) !!}
            @endif
        </p>
    </div>

    <div class="rgpd-notice">
        <strong>NOTICE RGPD:</strong> Ce document contient des données personnelles. 
        Conservation: 3 ans maximum. Anonymisation automatique après expiration. 
        Droits: Accès, rectification, suppression (contact RH). 
        @if($anonymize)
        <br><strong>Ce document a été anonymisé conformément au RGPD.</strong>
        @endif
    </div>
@endsection
