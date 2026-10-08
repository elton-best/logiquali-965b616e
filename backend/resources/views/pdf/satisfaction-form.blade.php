@extends('pdf.layouts.master')

@section('title', 'Fiche de Satisfaction - ' . $form->reference)

@section('styles')
    <style>
        .header .reference {
            font-size: 14pt;
            font-weight: bold;
            color: #666;
        }

        .info-section {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .info-section h2 {
            color: #1976D2;
            font-size: 14pt;
            margin: 0 0 10px 0;
            border-bottom: 2px solid #1976D2;
            padding-bottom: 5px;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .info-label {
            display: table-cell;
            width: 40%;
            font-weight: bold;
            color: #555;
        }

        .info-value {
            display: table-cell;
            width: 60%;
            color: #333;
        }

        .criteria-section {
            margin-bottom: 25px;
        }

        .criteria-section h2 {
            color: #1976D2;
            font-size: 14pt;
            margin: 0 0 15px 0;
            border-bottom: 2px solid #1976D2;
            padding-bottom: 5px;
        }

        .criterion {
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            padding: 12px;
            background-color: #fff;
            border-radius: 5px;
        }

        .criterion-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .criterion-rating {
            display: inline-block;
            margin-top: 5px;
        }

        .rating-bar {
            display: inline-block;
            height: 20px;
            background-color: #e0e0e0;
            width: 200px;
            border-radius: 3px;
            position: relative;
            vertical-align: middle;
        }

        .rating-fill {
            height: 100%;
            background-color: #4CAF50;
            border-radius: 3px;
            display: inline-block;
        }

        .rating-text {
            display: inline-block;
            margin-left: 10px;
            font-weight: bold;
            vertical-align: middle;
        }

        .score-section {
            background-color: #E3F2FD;
            border: 2px solid #1976D2;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 5px;
            text-align: center;
        }

        .score-section h2 {
            color: #1976D2;
            margin: 0 0 15px 0;
            font-size: 16pt;
        }

        .score-grid {
            display: table;
            width: 100%;
        }

        .score-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 10px;
        }

        .score-label {
            font-size: 10pt;
            color: #666;
            margin-bottom: 5px;
        }

        .score-value {
            font-size: 20pt;
            font-weight: bold;
            color: #1976D2;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 10pt;
            font-weight: bold;
            color: white;
        }

        .status-draft {
            background-color: #9E9E9E;
        }

        .status-submitted {
            background-color: #2196F3;
        }

        .status-reviewed {
            background-color: #4CAF50;
        }

        .satisfaction-very_satisfied {
            background-color: #4CAF50;
        }

        .satisfaction-satisfied {
            background-color: #8BC34A;
        }

        .satisfaction-dissatisfied {
            background-color: #FF9800;
        }

        .satisfaction-very_dissatisfied {
            background-color: #F44336;
        }

        .comments-section {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #fffef7;
            border-radius: 5px;
        }

        .comments-section h2 {
            color: #1976D2;
            font-size: 14pt;
            margin: 0 0 10px 0;
        }

        .comments-text {
            color: #333;
            font-style: italic;
            white-space: pre-wrap;
        }
    </style>
@endsection

@section('content')
    <div class="header">
        <h1>FICHE DE SATISFACTION CLIENT</h1>
        <div class="reference">{{ $form->ref }}</div>
    </div>

    <!-- Informations générales -->
    <div class="info-section">
        <h2>Informations Générales</h2>
        <div class="info-row">
            <div class="info-label">Date du sondage :</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($form->survey_date)->format('d/m/Y') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Site concerné :</div>
            <div class="info-value">{{ $form->site->name ?? 'Non spécifié' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Statut :</div>
            <div class="info-value">
                <span class="status-badge status-{{ $form->status }}">
                    @if($form->status === 'draft') Brouillon
                    @elseif($form->status === 'submitted') Soumis
                    @elseif($form->status === 'reviewed') Examiné
                    @endif
                </span>
            </div>
        </div>
        @if($form->reviewed_at)
            <div class="info-row">
                <div class="info-label">Examiné le :</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($form->reviewed_at)->format('d/m/Y à H:i') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Examiné par :</div>
                <div class="info-value">{{ $form->reviewer->name ?? 'N/A' }}</div>
            </div>
        @endif
    </div>

    <!-- Score global -->
    <div class="score-section">
        <h2>Résultats Globaux</h2>
        <div class="score-grid">
            <div class="score-item">
                <div class="score-label">Score Total</div>
                <div class="score-value">{{ $form->total_score }}/24</div>
            </div>
            <div class="score-item">
                <div class="score-label">Pourcentage</div>
                <div class="score-value">{{ number_format($form->satisfaction_percentage, 1) }}%</div>
            </div>
            <div class="score-item">
                <div class="score-label">Niveau de Satisfaction</div>
                <div>
                    <span class="status-badge satisfaction-{{ $form->satisfaction_level }}">
                        @if($form->satisfaction_level === 'very_satisfied') Très Satisfait
                        @elseif($form->satisfaction_level === 'satisfied') Satisfait
                        @elseif($form->satisfaction_level === 'dissatisfied') Insatisfait
                        @elseif($form->satisfaction_level === 'very_dissatisfied') Très Insatisfait
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Critères d'évaluation -->
    <div class="criteria-section">
        <h2>Détail des Critères (Échelle de 1 à 4)</h2>

        <div class="criterion">
            <div class="criterion-label">1. Aimabilité et écoute du client</div>
            <div class="criterion-rating">
                <div class="rating-bar">
                    <div class="rating-fill" style="width: {{ ($form->amabilite_ecoute / 4) * 100 }}%;"></div>
                </div>
                <span class="rating-text">{{ $form->amabilite_ecoute }}/4</span>
            </div>
        </div>

        <div class="criterion">
            <div class="criterion-label">2. Disponibilité / Spontanéité</div>
            <div class="criterion-rating">
                <div class="rating-bar">
                    <div class="rating-fill" style="width: {{ ($form->disponibilite_spontaneite / 4) * 100 }}%;">
                    </div>
                </div>
                <span class="rating-text">{{ $form->disponibilite_spontaneite }}/4</span>
            </div>
        </div>

        <div class="criterion">
            <div class="criterion-label">3. Rapidité dans le traitement des requêtes</div>
            <div class="criterion-rating">
                <div class="rating-bar">
                    <div class="rating-fill" style="width: {{ ($form->rapidite_traitement / 4) * 100 }}%;"></div>
                </div>
                <span class="rating-text">{{ $form->rapidite_traitement }}/4</span>
            </div>
        </div>

        <div class="criterion">
            <div class="criterion-label">4. Respect des délais de livraison</div>
            <div class="criterion-rating">
                <div class="rating-bar">
                    <div class="rating-fill" style="width: {{ ($form->respect_delais / 4) * 100 }}%;"></div>
                </div>
                <span class="rating-text">{{ $form->respect_delais }}/4</span>
            </div>
        </div>

        <div class="criterion">
            <div class="criterion-label">5. Conformité des produits livrés</div>
            <div class="criterion-rating">
                <div class="rating-bar">
                    <div class="rating-fill" style="width: {{ ($form->conformite_produits / 4) * 100 }}%;"></div>
                </div>
                <span class="rating-text">{{ $form->conformite_produits }}/4</span>
            </div>
        </div>

        <div class="criterion">
            <div class="criterion-label">6. Traitement des réclamations et plaintes</div>
            <div class="criterion-rating">
                <div class="rating-bar">
                    <div class="rating-fill" style="width: {{ ($form->traitement_reclamations / 4) * 100 }}%;">
                    </div>
                </div>
                <span class="rating-text">{{ $form->traitement_reclamations }}/4</span>
            </div>
        </div>
    </div>

    <!-- Commentaires -->
    @if($form->additional_comments)
        <div class="comments-section">
            <h2>Commentaires Additionnels</h2>
            <div class="comments-text">{{ $form->additional_comments }}</div>
        </div>
    @endif

    @if($form->reviewer_notes)
        <div class="comments-section">
            <h2>Notes de l'Examinateur</h2>
            <div class="comments-text">{{ $form->reviewer_notes }}</div>
        </div>
    @endif

    <!-- Footer mention (BestQHSE) -->
    <div style="margin-top: 40px; padding-top: 15px; border-top: 2px solid #ddd; text-align: center; font-size: 9pt; color: #666;">
        <p>BestQHSE - Système de Gestion de la Qualité</p>
    </div>
@endsection