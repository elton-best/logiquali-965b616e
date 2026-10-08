@extends('pdf.layouts.master')

@section('title', 'Évaluation Prestataire - ' . ($anonymize ? 'Anonyme' : ($provider['name'] ?? 'N/A')))

@section('styles')
    <style>
        .meta-info {
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #4472C4;
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
            width: 30%;
            color: #555;
        }
        .section-title {
            background-color: #4472C4;
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
            background-color: #2E3B55;
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
        .classification {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            text-align: center;
        }
        .classification-excellent {
            background-color: #C8E6C9;
            color: #2E7D32;
        }
        .classification-acceptable {
            background-color: #FFE082;
            color: #F57F17;
        }
        .classification-non-conforme {
            background-color: #FFCDD2;
            color: #C62828;
        }
        .actions-box {
            background-color: #E3F2FD;
            border-left: 4px solid #1976D2;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
@endsection

@section('content')
    <div class="header">
        <h1>FICHE D'ÉVALUATION PRESTATAIRE</h1>
        <div class="subtitle" style="font-size: 11pt; color: #666;">
            @if($anonymize)
                <strong style="color: #FF6600;">VERSION ANONYME - RGPD</strong>
            @else
                Évaluation performance fournisseur/partenaire (Conforme ISO 9001:2015 8.4.1)
            @endif
        </div>
    </div>

    <div class="meta-info">
        <table>
            <tr>
                <td class="label">Raison sociale:</td>
                <td>{{ $anonymize ? 'ANONYME' : ($provider['name'] ?? 'N/A') }}</td>
                <td class="label">Date évaluation:</td>
                <td>{{ $provider['evaluation_date'] ?? now()->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Type de prestation:</td>
                <td>{{ $provider['service_type'] ?? 'N/A' }}</td>
                <td class="label">Période évaluée:</td>
                <td>{{ $provider['period'] ?? 'Année ' . now()->year }}</td>
            </tr>
            <tr>
                <td class="label">Référent interne:</td>
                <td>{{ $anonymize ? 'CONFIDENTIEL' : ($provider['internal_contact'] ?? 'N/A') }}</td>
                <td class="label">Durée collaboration:</td>
                <td>{{ $provider['collaboration_duration'] ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section" style="page-break-inside: avoid;">
        <div class="section-title">1. CRITÈRES D'ÉVALUATION QUALITÉ</div>
        <table class="evaluation-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Critère</th>
                    <th style="width: 15%;">Note /5</th>
                    <th style="width: 15%;">Poids</th>
                    <th style="width: 30%;">Observations</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Qualité des prestations/livraisons</td>
                    <td class="rating">{{ $provider['quality_rating'] ?? 'N/A' }} / 5</td>
                    <td class="rating">30%</td>
                    <td>{{ $anonymize ? '***' : ($provider['quality_comment'] ?? '-') }}</td>
                </tr>
                <tr>
                    <td>Respect des délais</td>
                    <td class="rating">{{ $provider['timeliness_rating'] ?? 'N/A' }} / 5</td>
                    <td class="rating">25%</td>
                    <td>{{ $anonymize ? '***' : ($provider['timeliness_comment'] ?? '-') }}</td>
                </tr>
                <tr>
                    <td>Réactivité et communication</td>
                    <td class="rating">{{ $provider['communication_rating'] ?? 'N/A' }} / 5</td>
                    <td class="rating">20%</td>
                    <td>{{ $anonymize ? '***' : ($provider['communication_comment'] ?? '-') }}</td>
                </tr>
                <tr>
                    <td>Gestion des non-conformités</td>
                    <td class="rating">{{ $provider['nc_management_rating'] ?? 'N/A' }} / 5</td>
                    <td class="rating">15%</td>
                    <td>{{ $anonymize ? '***' : ($provider['nc_management_comment'] ?? '-') }}</td>
                </tr>
                <tr>
                    <td>Conformité réglementaire</td>
                    <td class="rating">{{ $provider['compliance_rating'] ?? 'N/A' }} / 5</td>
                    <td class="rating">10%</td>
                    <td>{{ $anonymize ? '***' : ($provider['compliance_comment'] ?? '-') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section" style="page-break-inside: avoid;">
        <div class="section-title">2. SYNTHÈSE QUANTITATIVE</div>
        <table class="evaluation-table">
            <thead>
                <tr>
                    <th>Indicateur</th>
                    <th style="width: 20%;">Valeur</th>
                    <th style="width: 20%;">Objectif</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Taux de conformité des livraisons</td>
                    <td class="rating">{{ $provider['conformity_rate'] ?? 'N/A' }}%</td>
                    <td class="rating">≥ 95%</td>
                </tr>
                <tr>
                    <td>Nombre de non-conformités</td>
                    <td class="rating">{{ $provider['nc_count'] ?? 'N/A' }}</td>
                    <td class="rating">< 5</td>
                </tr>
                <tr>
                    <td>Taux de respect des délais</td>
                    <td class="rating">{{ $provider['on_time_rate'] ?? 'N/A' }}%</td>
                    <td class="rating">≥ 90%</td>
                </tr>
                <tr>
                    <td>Délai moyen de réponse</td>
                    <td class="rating">{{ $provider['avg_response_time'] ?? 'N/A' }}</td>
                    <td class="rating">< 48h</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section" style="page-break-inside: avoid;">
        <div class="section-title">3. CLASSIFICATION ET DÉCISION</div>
        
        @php
            $avgRating = $provider['overall_rating'] ?? 3.5;
            $classification = $avgRating >= 4 ? 'excellent' : ($avgRating >= 3 ? 'acceptable' : 'non-conforme');
            $classLabel = $avgRating >= 4 ? 'EXCELLENT' : ($avgRating >= 3 ? 'ACCEPTABLE' : 'NON CONFORME');
            $classStyle = 'classification-' . $classification;
        @endphp
        
        <table style="width: 100%; margin-top: 15px;">
            <tr>
                <td style="width: 40%; font-weight: bold; font-size: 12pt;">Note globale moyenne:</td>
                <td style="width: 20%; font-size: 14pt; font-weight: bold; text-align: center;">{{ number_format($avgRating, 2) }} / 5</td>
                <td style="width: 40%;">
                    <div class="classification {{ $classStyle }}">{{ $classLabel }}</div>
                </td>
            </tr>
        </table>

        <div class="actions-box">
            <strong style="font-size: 11pt;">Décision:</strong>
            <p style="margin: 5px 0;">
                @if($classification === 'excellent')
                    ✅ Renouvellement contrat recommandé - Maintien collaboration
                @elseif($classification === 'acceptable')
                    ⚠️ Plan d'amélioration requis - Suivi renforcé trimestriel
                @else
                    ❌ Plan d'actions correctif obligatoire sous 30 jours ou déréférencement
                @endif
            </p>

            @if(!$anonymize && isset($provider['action_plan']))
            <div style="margin-top: 10px;">
                <strong>Plan d'actions:</strong>
                <p style="margin: 5px 0; font-size: 9pt;">{{ $provider['action_plan'] }}</p>
            </div>
            @endif
        </div>
    </div>

    <div class="section" style="page-break-inside: avoid;">
        <div class="section-title">4. POINTS FORTS & AXES D'AMÉLIORATION</div>
        <table style="width: 100%; margin-top: 10px;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <strong style="color: #2E7D32;">✓ Points forts:</strong>
                    <ul style="font-size: 9pt; margin: 5px 0;">
                        @if($anonymize)
                            <li><em>Contenu anonymisé</em></li>
                        @else
                            @foreach($provider['strengths'] ?? ['N/A'] as $strength)
                                <li>{{ $strength }}</li>
                            @endforeach
                        @endif
                    </ul>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                    <strong style="color: #C62828;">⚠ Axes d'amélioration:</strong>
                    <ul style="font-size: 9pt; margin: 5px 0;">
                        @if($anonymize)
                            <li><em>Contenu anonymisé</em></li>
                        @else
                            @foreach($provider['improvements'] ?? ['N/A'] as $improvement)
                                <li>{{ $improvement }}</li>
                            @endforeach
                        @endif
                    </ul>
                </td>
            </tr>
        </table>
    </div>
@endsection
